<?php

declare(strict_types=1);

final class ActionThrottle
{
    public static function hit(
        string $action,
        string $subject,
        string $ipAddress,
        int $maxAttempts = 3,
        int $windowSeconds = 900,
        int $blockSeconds = 900
    ): int {
        $action = mb_substr(trim($action), 0, 50);
        $subjectHash = hash('sha256', strtolower(trim($subject)));
        $ipAddress = mb_substr(trim($ipAddress), 0, 64);
        $now = now_string();
        $row = self::find($action, $subjectHash, $ipAddress);

        if ($row !== null && trim((string) ($row['blocked_until'] ?? '')) !== '') {
            $remaining = strtotime((string) $row['blocked_until']) - time();
            if ($remaining > 0) {
                return $remaining;
            }
        }

        if ($row === null) {
            $stmt = Database::connection()->prepare(<<<'SQL'
INSERT INTO mail_request_attempts (action, subject_hash, ip_address, attempt_count, window_started_at, blocked_until, updated_at)
VALUES (:action, :subject_hash, :ip_address, 1, :window_started_at, NULL, :updated_at)
SQL);
            $stmt->execute([
                'action' => $action,
                'subject_hash' => $subjectHash,
                'ip_address' => $ipAddress,
                'window_started_at' => $now,
                'updated_at' => $now,
            ]);
            return 0;
        }

        $windowStarted = strtotime((string) $row['window_started_at']);
        $count = (int) $row['attempt_count'];
        if ($windowStarted <= 0 || (time() - $windowStarted) > $windowSeconds) {
            $count = 0;
            $windowStarted = time();
        }

        $count++;
        $blockedUntil = $count > $maxAttempts ? gmdate('Y-m-d H:i:s', time() + $blockSeconds) : null;
        $stmt = Database::connection()->prepare(<<<'SQL'
UPDATE mail_request_attempts SET
    attempt_count = :attempt_count,
    window_started_at = :window_started_at,
    blocked_until = :blocked_until,
    updated_at = :updated_at
WHERE action = :action AND subject_hash = :subject_hash AND ip_address = :ip_address
SQL);
        $stmt->execute([
            'attempt_count' => $count,
            'window_started_at' => gmdate('Y-m-d H:i:s', $windowStarted),
            'blocked_until' => $blockedUntil,
            'updated_at' => $now,
            'action' => $action,
            'subject_hash' => $subjectHash,
            'ip_address' => $ipAddress,
        ]);

        return $blockedUntil === null ? 0 : $blockSeconds;
    }

    public static function clearForSubject(string $subject): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM mail_request_attempts WHERE subject_hash = :subject_hash');
        $stmt->execute(['subject_hash' => hash('sha256', strtolower(trim($subject)))]);
    }

    private static function find(string $action, string $subjectHash, string $ipAddress): ?array
    {
        $stmt = Database::connection()->prepare(<<<'SQL'
SELECT * FROM mail_request_attempts
WHERE action = :action AND subject_hash = :subject_hash AND ip_address = :ip_address
SQL);
        $stmt->execute([
            'action' => $action,
            'subject_hash' => $subjectHash,
            'ip_address' => $ipAddress,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
