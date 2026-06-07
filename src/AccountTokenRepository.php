<?php

declare(strict_types=1);

final class AccountTokenRepository
{
    private const VERIFICATION_LIFETIME_SECONDS = 86400;
    private const RESET_LIFETIME_SECONDS = 3600;

    public static function sendVerificationEmail(int $userId): bool
    {
        $user = self::findUser($userId);
        if ($user === null || trim((string) ($user['email_verified_at'] ?? '')) !== '') {
            return true;
        }

        $token = self::issueToken('email_verification_tokens', $userId, self::VERIFICATION_LIFETIME_SECONDS);
        $url = app_url() . '/verify-email/confirm/' . $token;
        $subject = 'Xác minh email cho ' . app_name();
        $html = self::emailLayout(
            'Xác minh địa chỉ email',
            'Bấm nút bên dưới để xác minh email và tiếp tục sử dụng tài khoản. Link có hiệu lực trong 24 giờ.',
            'Xác minh email',
            $url
        );
        $sent = Mailer::send((string) $user['email'], $subject, $html, "Xác minh email: {$url}");
        ActivityLogger::log('account.email.verification.sent', $userId, $sent ? 'success' : 'failed');
        return $sent;
    }

    public static function verifyEmail(string $token): ?int
    {
        $row = self::findValidToken('email_verification_tokens', $token);
        if ($row === null) {
            return null;
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $now = now_string();
            $updateUser = $pdo->prepare('UPDATE users SET email_verified_at = :verified_at, updated_at = :updated_at WHERE id = :id');
            $updateUser->execute(['verified_at' => $now, 'updated_at' => $now, 'id' => (int) $row['user_id']]);
            $consume = $pdo->prepare('UPDATE email_verification_tokens SET consumed_at = :consumed_at WHERE user_id = :user_id AND consumed_at IS NULL');
            $consume->execute(['consumed_at' => $now, 'user_id' => (int) $row['user_id']]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        ActivityLogger::log('account.email.verified', (int) $row['user_id']);
        return (int) $row['user_id'];
    }

    public static function sendPasswordResetEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        $stmt = Database::connection()->prepare('SELECT id, name, email FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if (!$user) {
            return true;
        }

        $token = self::issueToken('password_reset_tokens', (int) $user['id'], self::RESET_LIFETIME_SECONDS);
        $url = app_url() . '/reset-password/' . $token;
        $subject = 'Đặt lại mật khẩu cho ' . app_name();
        $html = self::emailLayout(
            'Đặt lại mật khẩu',
            'Có yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Link có hiệu lực trong 60 phút. Nếu bạn không gửi yêu cầu này, hãy bỏ qua email.',
            'Đặt lại mật khẩu',
            $url
        );
        $sent = Mailer::send((string) $user['email'], $subject, $html, "Đặt lại mật khẩu: {$url}");
        ActivityLogger::log('account.password.reset.requested', (int) $user['id'], $sent ? 'success' : 'failed');
        return $sent;
    }

    public static function isResetTokenValid(string $token): bool
    {
        return self::findValidToken('password_reset_tokens', $token) !== null;
    }

    public static function resetPassword(string $token, string $passwordHash): ?int
    {
        $row = self::findValidToken('password_reset_tokens', $token);
        if ($row === null) {
            return null;
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $now = now_string();
            $updateUser = $pdo->prepare('UPDATE users SET password_hash = :password_hash, session_version = session_version + 1, updated_at = :updated_at WHERE id = :id');
            $updateUser->execute([
                'password_hash' => $passwordHash,
                'updated_at' => $now,
                'id' => (int) $row['user_id'],
            ]);
            $consume = $pdo->prepare('UPDATE password_reset_tokens SET consumed_at = :consumed_at WHERE user_id = :user_id AND consumed_at IS NULL');
            $consume->execute(['consumed_at' => $now, 'user_id' => (int) $row['user_id']]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        ActivityLogger::log('account.password.reset.completed', (int) $row['user_id']);
        return (int) $row['user_id'];
    }

    public static function revokePasswordResetsForUser(int $userId): void
    {
        $stmt = Database::connection()->prepare('UPDATE password_reset_tokens SET consumed_at = :consumed_at WHERE user_id = :user_id AND consumed_at IS NULL');
        $stmt->execute(['consumed_at' => now_string(), 'user_id' => $userId]);
    }

    private static function issueToken(string $table, int $userId, int $lifetimeSeconds): string
    {
        if (!in_array($table, ['email_verification_tokens', 'password_reset_tokens'], true)) {
            throw new InvalidArgumentException('Unsupported token table.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $now = now_string();
            $consume = $pdo->prepare("UPDATE {$table} SET consumed_at = :consumed_at WHERE user_id = :user_id AND consumed_at IS NULL");
            $consume->execute(['consumed_at' => $now, 'user_id' => $userId]);

            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("INSERT INTO {$table} (user_id, token_hash, expires_at, created_at, consumed_at) VALUES (:user_id, :token_hash, :expires_at, :created_at, NULL)");
            $stmt->execute([
                'user_id' => $userId,
                'token_hash' => hash('sha256', $token),
                'expires_at' => gmdate('Y-m-d H:i:s', time() + $lifetimeSeconds),
                'created_at' => $now,
            ]);
            $pdo->commit();
            return $token;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private static function findValidToken(string $table, string $token): ?array
    {
        if (!in_array($table, ['email_verification_tokens', 'password_reset_tokens'], true)) {
            return null;
        }
        $token = trim($token);
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $stmt = Database::connection()->prepare("SELECT id, user_id FROM {$table} WHERE token_hash = :token_hash AND consumed_at IS NULL AND expires_at > :now LIMIT 1");
        $stmt->execute(['token_hash' => hash('sha256', $token), 'now' => now_string()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function findUser(int $userId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name, email, email_verified_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function emailLayout(string $heading, string $message, string $button, string $url): string
    {
        $safeHeading = e($heading);
        $safeMessage = e($message);
        $safeButton = e($button);
        $safeUrl = e($url);
        $safeApp = e(app_name());

        return <<<HTML
<!doctype html>
<html lang="vi"><body style="font-family:Arial,Helvetica,sans-serif;background:#f4f8f7;padding:28px;color:#24332f">
<div style="max-width:620px;margin:auto;background:#fff;border:1px solid #d9e6e2;border-radius:14px;padding:26px">
<h1 style="font-size:22px;margin:0 0 12px">{$safeHeading}</h1>
<p style="line-height:1.6">{$safeMessage}</p>
<p style="margin:22px 0"><a href="{$safeUrl}" style="display:inline-block;background:#087f5b;color:#fff;text-decoration:none;padding:11px 16px;border-radius:8px;font-weight:700">{$safeButton}</a></p>
<p style="font-size:12px;color:#64746f;word-break:break-all">Nếu nút không hoạt động, mở link: {$safeUrl}</p>
<p style="font-size:12px;color:#64746f">{$safeApp}</p>
</div></body></html>
HTML;
    }
}
