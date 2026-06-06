<?php

declare(strict_types=1);

final class LoginThrottle
{
    private const MAX_FAILURES = 5;
    private const WINDOW_SECONDS = 900;
    private const BLOCK_SECONDS = 900;

    public static function remainingBlockSeconds(string $email, string $ipAddress): int
    {
        $row = self::find($email, $ipAddress);
        if ($row === null || trim((string) ($row['blocked_until'] ?? '')) === '') {
            return 0;
        }

        return max(0, strtotime((string) $row['blocked_until']) - time());
    }

    public static function recordFailure(string $email, string $ipAddress): int
    {
        $pdo = Database::connection();
        $emailHash = self::emailHash($email);
        $now = now_string();
        $row = self::findByKey($emailHash, $ipAddress);

        if ($row === null) {
            $stmt = $pdo->prepare(<<<'SQL'
INSERT INTO login_attempts (email_hash, ip_address, attempt_count, window_started_at, blocked_until, updated_at)
VALUES (:email_hash, :ip_address, 1, :window_started_at, NULL, :updated_at)
SQL);
            $stmt->execute([
                'email_hash' => $emailHash,
                'ip_address' => $ipAddress,
                'window_started_at' => $now,
                'updated_at' => $now,
            ]);
            return 0;
        }

        $windowStartedAt = strtotime((string) $row['window_started_at']);
        $count = (int) $row['attempt_count'];
        if ($windowStartedAt <= 0 || (time() - $windowStartedAt) > self::WINDOW_SECONDS) {
            $count = 0;
            $windowStartedAt = time();
        }

        $count++;
        $blockedUntil = $count >= self::MAX_FAILURES ? gmdate('Y-m-d H:i:s', time() + self::BLOCK_SECONDS) : null;

        $stmt = $pdo->prepare(<<<'SQL'
UPDATE login_attempts SET
    attempt_count = :attempt_count,
    window_started_at = :window_started_at,
    blocked_until = :blocked_until,
    updated_at = :updated_at
WHERE email_hash = :email_hash AND ip_address = :ip_address
SQL);
        $stmt->execute([
            'attempt_count' => $count,
            'window_started_at' => gmdate('Y-m-d H:i:s', $windowStartedAt),
            'blocked_until' => $blockedUntil,
            'updated_at' => $now,
            'email_hash' => $emailHash,
            'ip_address' => $ipAddress,
        ]);

        return $blockedUntil === null ? 0 : self::BLOCK_SECONDS;
    }

    public static function clear(string $email, string $ipAddress): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM login_attempts WHERE email_hash = :email_hash AND ip_address = :ip_address');
        $stmt->execute([
            'email_hash' => self::emailHash($email),
            'ip_address' => $ipAddress,
        ]);
    }

    private static function find(string $email, string $ipAddress): ?array
    {
        return self::findByKey(self::emailHash($email), $ipAddress);
    }

    private static function findByKey(string $emailHash, string $ipAddress): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM login_attempts WHERE email_hash = :email_hash AND ip_address = :ip_address');
        $stmt->execute([
            'email_hash' => $emailHash,
            'ip_address' => $ipAddress,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function emailHash(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }
}
