<?php

declare(strict_types=1);

final class AccountRepository
{
    public static function updateProfile(int $userId, string $name): array
    {
        $name = trim($name);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            return ['Họ tên cần có từ 2 đến 120 ký tự.'];
        }

        $stmt = Database::connection()->prepare('UPDATE users SET name = :name, updated_at = :updated_at WHERE id = :id');
        $stmt->execute(['name' => $name, 'updated_at' => now_string(), 'id' => $userId]);
        ActivityLogger::log('account.profile.updated', $userId);
        return [];
    }

    public static function exportPersonalData(int $userId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT name, email, email_verified_at, created_at, updated_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $account = $stmt->fetch();
        if (!$account) {
            throw new RuntimeException('Không tìm thấy tài khoản.');
        }

        $resumes = [];
        foreach (ResumeRepository::allForUser($userId) as $resume) {
            $resumes[] = [
                'title' => (string) $resume['title'],
                'template' => (string) $resume['template'],
                'full_name' => (string) $resume['full_name'],
                'job_title' => (string) $resume['job_title'],
                'email' => (string) $resume['email'],
                'phone' => (string) $resume['phone'],
                'location' => (string) $resume['location'],
                'summary' => (string) $resume['summary'],
                'sections' => decode_sections((string) $resume['sections_json']),
                'created_at' => (string) $resume['created_at'],
                'updated_at' => (string) $resume['updated_at'],
            ];
        }

        $activityStmt = $pdo->prepare('SELECT action, status, ip_address, created_at FROM activity_logs WHERE user_id = :user_id ORDER BY id DESC');
        $activityStmt->execute(['user_id' => $userId]);

        $shareStmt = $pdo->prepare(<<<'SQL'
SELECT r.title AS resume_title, s.expires_at, s.created_at, s.revoked_at, s.last_viewed_at, s.view_count
FROM resume_shares s
INNER JOIN resumes r ON r.id = s.resume_id
WHERE s.user_id = :user_id
ORDER BY s.id DESC
SQL);
        $shareStmt->execute(['user_id' => $userId]);

        ActivityLogger::log('account.data.exported', $userId);
        return [
            'schema_version' => 1,
            'exported_at' => gmdate('c'),
            'application' => app_name(),
            'account' => $account,
            'resumes' => $resumes,
            'resume_shares' => $shareStmt->fetchAll(),
            'activities' => $activityStmt->fetchAll(),
        ];
    }

    public static function deleteAccount(int $userId, string $password, string $confirmation): array
    {
        if ($confirmation !== 'XOA') {
            return ['Hãy nhập chính xác XOA để xác nhận xóa tài khoản.'];
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT email, password_hash FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            ActivityLogger::log('account.delete.failed', $userId, 'failed');
            return ['Mật khẩu hiện tại chưa đúng.'];
        }

        $pdo->beginTransaction();
        try {
            $deleteLogs = $pdo->prepare('DELETE FROM activity_logs WHERE user_id = :user_id');
            $deleteLogs->execute(['user_id' => $userId]);
            $deleteLoginAttempts = $pdo->prepare('DELETE FROM login_attempts WHERE email_hash = :email_hash');
            $deleteLoginAttempts->execute(['email_hash' => hash('sha256', strtolower(trim((string) $user['email'])))]);
            ActionThrottle::clearForSubject((string) $user['email']);
            ActionThrottle::clearForSubject((string) $userId);
            $deleteUser = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $deleteUser->execute(['id' => $userId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        return [];
    }
}
