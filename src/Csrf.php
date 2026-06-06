<?php

declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function verifyRequest(): void
    {
        $token = (string) ($_POST['_token'] ?? '');
        if (!hash_equals(self::token(), $token)) {
            http_response_code(419);
            exit('Phiên làm việc đã hết hạn hoặc CSRF token không hợp lệ. Hãy quay lại và thử lại.');
        }
    }
}
