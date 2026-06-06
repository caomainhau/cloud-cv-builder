<?php

declare(strict_types=1);

final class Auth
{
    private static ?array $cachedUser = null;

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
        if (mb_strlen($password) < 8) {
            $errors[] = 'Mật khẩu cần có ít nhất 8 ký tự.';
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
        return [];
    }

    public static function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        self::enforceLoginThrottle();

        $stmt = Database::connection()->prepare('SELECT id, password_hash FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            self::recordFailedLogin();
            return false;
        }

        unset($_SESSION['_login_failures']);
        self::loginUserId((int) $user['id']);
        return true;
    }

    public static function logout(): void
    {
        self::$cachedUser = null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    private static function loginUserId(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        self::$cachedUser = null;
    }

    private static function enforceLoginThrottle(): void
    {
        $data = $_SESSION['_login_failures'] ?? ['count' => 0, 'started_at' => time()];
        if ((time() - (int) $data['started_at']) > 300) {
            unset($_SESSION['_login_failures']);
            return;
        }

        if ((int) $data['count'] >= 5) {
            http_response_code(429);
            exit('Bạn đã nhập sai mật khẩu quá nhiều lần. Hãy thử lại sau khoảng 5 phút.');
        }
    }

    private static function recordFailedLogin(): void
    {
        $data = $_SESSION['_login_failures'] ?? ['count' => 0, 'started_at' => time()];
        if ((time() - (int) $data['started_at']) > 300) {
            $data = ['count' => 0, 'started_at' => time()];
        }
        $data['count'] = (int) $data['count'] + 1;
        $_SESSION['_login_failures'] = $data;
    }
}
