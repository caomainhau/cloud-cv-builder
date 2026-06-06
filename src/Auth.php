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

        $stmt = Database::connection()->prepare('SELECT id, name, email, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            unset($_SESSION['user_id']);
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

        if (Database::driver() === 'pgsql') {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, created_at, updated_at) VALUES (:name, :email, :password_hash, :created_at, :updated_at) RETURNING id');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $hash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $userId = (int) $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, created_at, updated_at) VALUES (:name, :email, :password_hash, :created_at, :updated_at)');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $hash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $userId = (int) $pdo->lastInsertId();
        }

        self::loginUserId($userId);
        ActivityLogger::log('account.register', $userId);
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

        $stmt = Database::connection()->prepare('SELECT id, password_hash FROM users WHERE email = :email');
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
        self::loginUserId((int) $user['id']);
        ActivityLogger::log('auth.login.success', (int) $user['id']);
        return true;
    }

    public static function lastLoginError(): string
    {
        return self::$lastLoginError;
    }

    public static function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmation): array
    {
        $errors = [];
        if ($newPassword !== $confirmation) {
            $errors[] = 'Mật khẩu mới và phần xác nhận chưa trùng khớp.';
        }
        if (mb_strlen($newPassword) < 8 || mb_strlen($newPassword) > 255) {
            $errors[] = 'Mật khẩu mới cần có từ 8 đến 255 ký tự.';
        }
        if ($errors !== []) {
            return $errors;
        }

        $stmt = Database::connection()->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $hash = $stmt->fetchColumn();

        if (!is_string($hash) || !password_verify($currentPassword, $hash)) {
            ActivityLogger::log('account.password.failed', $userId, 'failed');
            return ['Mật khẩu hiện tại chưa đúng.'];
        }
        if (password_verify($newPassword, $hash)) {
            return ['Mật khẩu mới cần khác mật khẩu hiện tại.'];
        }

        $update = Database::connection()->prepare('UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => now_string(),
            'id' => $userId,
        ]);

        session_regenerate_id(true);
        ActivityLogger::log('account.password.changed', $userId);
        return [];
    }

    public static function logout(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId > 0) {
            ActivityLogger::log('auth.logout', $userId);
        }

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

    private static function loginUserId(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        self::$cachedUser = null;
    }
}
