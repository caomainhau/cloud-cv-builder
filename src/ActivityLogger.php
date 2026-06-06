<?php

declare(strict_types=1);

final class ActivityLogger
{
    public static function log(string $action, ?int $userId = null, string $status = 'success', array $metadata = []): void
    {
        try {
            $action = mb_substr(trim($action), 0, 120);
            if ($action === '') {
                return;
            }

            $status = in_array($status, ['success', 'failed', 'blocked', 'denied'], true) ? $status : 'success';
            $metadataJson = json_encode(self::sanitizeMetadata($metadata), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($metadataJson === false) {
                $metadataJson = '{}';
            }

            $stmt = Database::connection()->prepare(<<<'SQL'
INSERT INTO activity_logs (user_id, action, status, ip_address, user_agent, metadata_json, created_at)
VALUES (:user_id, :action, :status, :ip_address, :user_agent, :metadata_json, :created_at)
SQL);
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'status' => $status,
                'ip_address' => mb_substr(client_ip(), 0, 64),
                'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
                'metadata_json' => mb_substr($metadataJson, 0, 3000),
                'created_at' => now_string(),
            ]);
        } catch (Throwable) {
            // Logging must never interrupt the primary user action.
        }
    }

    public static function recentForUser(int $userId, int $limit = 30): array
    {
        $limit = max(1, min($limit, 100));
        $stmt = Database::connection()->prepare("SELECT action, status, ip_address, created_at FROM activity_logs WHERE user_id = :user_id ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    private static function sanitizeMetadata(array $metadata): array
    {
        $clean = [];
        foreach (array_slice($metadata, 0, 20, true) as $key => $value) {
            $key = mb_substr((string) $key, 0, 80);
            if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $clean[$key] = $value;
                continue;
            }
            if (is_string($value)) {
                $clean[$key] = mb_substr($value, 0, 300);
                continue;
            }
            if (is_array($value)) {
                $clean[$key] = array_slice(array_map(static fn ($item): string => mb_substr((string) $item, 0, 100), $value), 0, 20);
            }
        }
        return $clean;
    }
}
