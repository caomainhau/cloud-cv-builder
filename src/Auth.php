<?php

declare(strict_types=1);

final class Auth
{
    private static ?array $cachedUser = null;
    private static string $lastLoginError = 'Email hoặc mật khẩu chưa đúng.';

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $stmt = Database::connection()->prepare('SELECT id, name, email, email_verified_at, session_version, created_at, updated_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['session_version']);
            return null;
        }
        if ((int) ($_SESSION['session_version'] ?? 0) !== (int) ($user['session_version'] ?? 1)) {
            self::clearSession();
            return null;
        }

        self::$cachedUser = $user;
        return self::$cachedUser;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireUser(): array
    {
        $user = self::user();
        if ($user === null) {
            flash('warning', 'Bạn cần đăng nhập để tiếp tục.');
            redirect('/login');
        }

        return $user;
    }

    public static function requireVerifiedUser(): array
    {
        $user = self::requireUser();
        if (self::emailVerificationRequired() && !self::isVerified($user)) {
            flash('warning', 'Hãy xác minh email trước khi sử dụng khu vực CV.');
            redirect('/verify-email');
        }
        return $user;
    }

    public static function emailVerificationRequired(): bool
    {
        return env_bool('REQUIRE_EMAIL_VERIFICATION', false);
    }

    public static function isVerified(?array $user = null): bool
    {
        $user ??= self::user();
        return $user !== null && trim((string) ($user['email_verified_at'] ?? '')) !== '';
    }

    public static function afterLoginPath(): string
    {
        return self::emailVerificationRequired() && !self::isVerified() ? '/verify-email' : '/dashboard';
    }

    public static function register(string $name, string $email, string $password): array
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors[] = 'Họ tên cần có từ 2 đến 120 ký tự.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $errors[] = 'Email không hợp lệ.';
        }
        if (mb_strlen($password) < 8 || mb_strlen($password) > 255) {
            $errors[] = 'Mật khẩu cần có từ 8 đến 255 ký tự.';
        }

        if ($errors !== []) {
            return $errors;
        }

        $pdo = Database::connection();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            return ['Email này đã được sử dụng.'];
        }

        $now = now_string();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $emailVerifiedAt = self::emailVerificationRequired() ? null : $now;
        $params = [
            'name' => $name,
            'email' => $email,
            'password_hash' => $hash,
            'email_verified_at' => $emailVerifiedAt,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Database::driver() === 'pgsql') {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, email_verified_at, created_at, updated_at) VALUES (:name, :email, :password_hash, :email_verified_at, :created_at, :updated_at) RETURNING id');
            $stmt->execute($params);
            $userId = (int) $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, email_verified_at, created_at, updated_at) VALUES (:name, :email, :password_hash, :email_verified_at, :created_at, :updated_at)');
            $stmt->execute($params);
            $userId = (int) $pdo->lastInsertId();
        }

        self::loginUserId($userId);
        ActivityLogger::log('account.register', $userId);
        if (self::emailVerificationRequired()) {
            $sent = AccountTokenRepository::sendVerificationEmail($userId);
            flash($sent ? 'success' : 'warning', $sent
                ? 'Tài khoản đã được tạo. Hãy mở email để xác minh tài khoản.'
                : 'Tài khoản đã được tạo nhưng chưa gửi được email xác minh. Hãy thử gửi lại sau.');
        }
        return [];
    }

    public static function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        $ipAddress = client_ip();
        self::$lastLoginError = 'Email hoặc mật khẩu chưa đúng.';

        $remaining = LoginThrottle::remainingBlockSeconds($email, $ipAddress);
        if ($remaining > 0) {
            self::$lastLoginError = 'Bạn đã nhập sai mật khẩu quá nhiều lần. Hãy thử lại sau khoảng ' . max(1, (int) ceil($remaining / 60)) . ' phút.';
            ActivityLogger::log('auth.login.blocked', null, 'blocked', [
                'email_hash' => hash('sha256', $email),
                'remaining_seconds' => $remaining,
            ]);
            return false;
        }

        $stmt = Database::connection()->prepare('SELECT id, password_hash, session_version FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        $userId = $user ? (int) $user['id'] : null;

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $blockedFor = LoginThrottle::recordFailure($email, $ipAddress);
            if ($blockedFor > 0) {
                self::$lastLoginError = 'Bạn đã nhập sai mật khẩu quá nhiều lần. Tài khoản tạm thời bị khóa trong khoảng 15 phút.';
            }
            ActivityLogger::log('auth.login.failed', $userId, 'failed', [
                'email_hash' => hash('sha256', $email),
                'blocked' => $blockedFor > 0,
            ]);
            return false;
        }

        LoginThrottle::clear($email, $ipAddress);
        self::loginUserId((int) $user['id'], (int) ($user['session_version'] ?? 1));
        ActivityLogger::log('auth.login.success', (int) $user['id']);
        return true;
    }

    public static function lastLoginError(): string
    {
        return self::$lastLoginError;
    }

    public static function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmation): array
    {
        $errors = self::validateNewPassword($newPassword, $confirmation);
        if ($errors !== []) {
            return $errors;
        }

        $stmt = Database::connection()->prepare('SELECT email, password_hash FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, (string) $user['password_hash'])) {
            ActivityLogger::log('account.password.failed', $userId, 'failed');
            return ['Mật khẩu hiện tại chưa đúng.'];
        }
        if (password_verify($newPassword, (string) $user['password_hash'])) {
            return ['Mật khẩu mới cần khác mật khẩu hiện tại.'];
        }

        $update = Database::connection()->prepare('UPDATE users SET password_hash = :password_hash, session_version = session_version + 1, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => now_string(),
            'id' => $userId,
        ]);

        LoginThrottle::clearAllForEmail((string) $user['email']);
        AccountTokenRepository::revokePasswordResetsForUser($userId);
        $versionStmt = Database::connection()->prepare('SELECT session_version FROM users WHERE id = :id');
        $versionStmt->execute(['id' => $userId]);
        $_SESSION['session_version'] = (int) ($versionStmt->fetchColumn() ?: 1);
        session_regenerate_id(true);
        ActivityLogger::log('account.password.changed', $userId);
        return [];
    }

    public static function requestVerificationEmail(int $userId): array
    {
        $user = self::user();
        if ($user === null || (int) $user['id'] !== $userId) {
            return ['Không tìm thấy tài khoản.'];
        }
        if (self::isVerified($user)) {
            return [];
        }

        $remaining = ActionThrottle::hit('email_verification', (string) $userId, client_ip(), 3, 900, 900);
        if ($remaining > 0) {
            ActivityLogger::log('account.email.verification.blocked', $userId, 'blocked', ['remaining_seconds' => $remaining]);
            return ['Bạn đã yêu cầu gửi email quá nhiều lần. Hãy thử lại sau khoảng ' . max(1, (int) ceil($remaining / 60)) . ' phút.'];
        }

        if (!AccountTokenRepository::sendVerificationEmail($userId)) {
            return ['Chưa thể gửi email xác minh. Hãy kiểm tra cấu hình email hoặc thử lại sau.'];
        }
        return [];
    }

    public static function verifyEmail(string $token): bool
    {
        $verifiedUserId = AccountTokenRepository::verifyEmail($token);
        if ($verifiedUserId === null) {
            return false;
        }
        if ((int) ($_SESSION['user_id'] ?? 0) === $verifiedUserId) {
            self::$cachedUser = null;
        }
        ActionThrottle::clearForSubject((string) $verifiedUserId);
        return true;
    }

    public static function requestPasswordReset(string $email): void
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            return;
        }

        $remaining = ActionThrottle::hit('password_reset', $email, client_ip(), 3, 900, 900);
        if ($remaining > 0) {
            ActivityLogger::log('account.password.reset.blocked', null, 'blocked', [
                'email_hash' => hash('sha256', $email),
                'remaining_seconds' => $remaining,
            ]);
            return;
        }

        AccountTokenRepository::sendPasswordResetEmail($email);
    }

    public static function resetPassword(string $token, string $password, string $confirmation): array
    {
        $errors = self::validateNewPassword($password, $confirmation);
        if ($errors !== []) {
            return $errors;
        }

        $userId = AccountTokenRepository::resetPassword($token, password_hash($password, PASSWORD_DEFAULT));
        if ($userId === null) {
            return ['Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'];
        }

        $email = self::emailForUser($userId);
        if ($email !== '') {
            LoginThrottle::clearAllForEmail($email);
            ActionThrottle::clearForSubject($email);
        }
        return [];
    }

    public static function logout(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId > 0) {
            ActivityLogger::log('auth.logout', $userId);
        }
        self::clearSession();
    }

    public static function clearSession(): void
    {
        self::$cachedUser = null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    public static function refreshCachedUser(): void
    {
        self::$cachedUser = null;
    }

    private static function loginUserId(int $userId, ?int $sessionVersion = null): void
    {
        if ($sessionVersion === null) {
            $stmt = Database::connection()->prepare('SELECT session_version FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);
            $sessionVersion = (int) ($stmt->fetchColumn() ?: 1);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['session_version'] = $sessionVersion;
        self::$cachedUser = null;
    }

    private static function validateNewPassword(string $password, string $confirmation): array
    {
        $errors = [];
        if ($password !== $confirmation) {
            $errors[] = 'Mật khẩu mới và phần xác nhận chưa trùng khớp.';
        }
        if (mb_strlen($password) < 8 || mb_strlen($password) > 255) {
            $errors[] = 'Mật khẩu mới cần có từ 8 đến 255 ký tự.';
        }
        return $errors;
    }

    private static function emailForUser(int $userId): string
    {
        $stmt = Database::connection()->prepare('SELECT email FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        return (string) ($stmt->fetchColumn() ?: '');
    }
}
