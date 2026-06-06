<?php

declare(strict_types=1);

final class ResumeShareRepository
{
    public static function activeForResume(int $resumeId, int $userId): ?array
    {
        ResumeRepository::requireOwned($resumeId, $userId);
        $stmt = Database::connection()->prepare(<<<'SQL'
SELECT id, resume_id, expires_at, created_at, last_viewed_at, view_count
FROM resume_shares
WHERE resume_id = :resume_id AND user_id = :user_id AND revoked_at IS NULL
ORDER BY id DESC
LIMIT 1
SQL);
        $stmt->execute(['resume_id' => $resumeId, 'user_id' => $userId]);
        $share = $stmt->fetch();
        if (!$share) {
            return null;
        }

        if (self::isExpired($share['expires_at'] ?? null)) {
            self::revoke($resumeId, $userId, false);
            return null;
        }

        return $share;
    }

    public static function create(int $resumeId, int $userId, int $validDays): string
    {
        ResumeRepository::requireOwned($resumeId, $userId);
        $validDays = in_array($validDays, [0, 7, 30, 90], true) ? $validDays : 30;
        self::revoke($resumeId, $userId, false);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = $validDays > 0 ? gmdate('Y-m-d H:i:s', time() + ($validDays * 86400)) : null;
        $createdAt = now_string();

        $stmt = Database::connection()->prepare(<<<'SQL'
INSERT INTO resume_shares (resume_id, user_id, token_hash, expires_at, created_at, revoked_at, last_viewed_at, view_count)
VALUES (:resume_id, :user_id, :token_hash, :expires_at, :created_at, NULL, NULL, 0)
SQL);
        $stmt->execute([
            'resume_id' => $resumeId,
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'created_at' => $createdAt,
        ]);

        ActivityLogger::log('resume.share.created', $userId, 'success', [
            'resume_id' => $resumeId,
            'valid_days' => $validDays,
        ]);

        return app_url() . '/share/' . $token;
    }

    public static function revoke(int $resumeId, int $userId, bool $logActivity = true): void
    {
        ResumeRepository::requireOwned($resumeId, $userId);
        $stmt = Database::connection()->prepare(<<<'SQL'
UPDATE resume_shares
SET revoked_at = :revoked_at
WHERE resume_id = :resume_id AND user_id = :user_id AND revoked_at IS NULL
SQL);
        $stmt->execute([
            'resume_id' => $resumeId,
            'user_id' => $userId,
            'revoked_at' => now_string(),
        ]);

        if ($logActivity) {
            ActivityLogger::log('resume.share.revoked', $userId, 'success', ['resume_id' => $resumeId]);
        }
    }

    public static function findPublicByToken(string $token): ?array
    {
        $token = trim($token);
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $stmt = Database::connection()->prepare(<<<'SQL'
SELECT r.*, s.id AS share_id, s.expires_at AS share_expires_at
FROM resume_shares s
INNER JOIN resumes r ON r.id = s.resume_id
WHERE s.token_hash = :token_hash
  AND s.revoked_at IS NULL
  AND (s.expires_at IS NULL OR s.expires_at > :now)
LIMIT 1
SQL);
        $stmt->execute([
            'token_hash' => hash('sha256', $token),
            'now' => now_string(),
        ]);
        $resume = $stmt->fetch();
        if (!$resume) {
            return null;
        }

        $update = Database::connection()->prepare(<<<'SQL'
UPDATE resume_shares
SET view_count = view_count + 1, last_viewed_at = :last_viewed_at
WHERE id = :id
SQL);
        $update->execute([
            'id' => (int) $resume['share_id'],
            'last_viewed_at' => now_string(),
        ]);

        return $resume;
    }

    private static function isExpired(mixed $expiresAt): bool
    {
        if (!is_string($expiresAt) || trim($expiresAt) === '') {
            return false;
        }

        return strtotime($expiresAt) <= time();
    }
}
