<?php

declare(strict_types=1);

final class ResumeRepository
{
    public static function allForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM resumes WHERE user_id = :user_id ORDER BY updated_at DESC, id DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function findOwned(int $resumeId, int $userId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM resumes WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $resumeId, 'user_id' => $userId]);
        $resume = $stmt->fetch();
        return $resume ?: null;
    }

    public static function requireOwned(int $resumeId, int $userId): array
    {
        $resume = self::findOwned($resumeId, $userId);
        if ($resume === null) {
            http_response_code(404);
            exit('Không tìm thấy CV hoặc bạn không có quyền truy cập.');
        }
        return $resume;
    }

    public static function create(int $userId, string $title): int
    {
        $pdo = Database::connection();
        $title = trim($title) !== '' ? mb_substr(trim($title), 0, 160) : 'CV mới';
        $now = now_string();
        $params = [
            'user_id' => $userId,
            'title' => $title,
            'template' => 'ats-simple',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Database::driver() === 'pgsql') {
            $stmt = $pdo->prepare('INSERT INTO resumes (user_id, title, template, created_at, updated_at) VALUES (:user_id, :title, :template, :created_at, :updated_at) RETURNING id');
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        }

        $stmt = $pdo->prepare('INSERT INTO resumes (user_id, title, template, created_at, updated_at) VALUES (:user_id, :title, :template, :created_at, :updated_at)');
        $stmt->execute($params);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $resumeId, int $userId, array $input): void
    {
        self::requireOwned($resumeId, $userId);
        $template = in_array(($input['template'] ?? ''), ['ats-simple', 'modern-minimal'], true)
            ? (string) $input['template']
            : 'ats-simple';

        $sections = self::sanitizeSections((string) ($input['sections_json'] ?? '{}'));
        $stmt = Database::connection()->prepare(<<<'SQL'
UPDATE resumes SET
    title = :title,
    template = :template,
    full_name = :full_name,
    job_title = :job_title,
    email = :email,
    phone = :phone,
    location = :location,
    summary = :summary,
    sections_json = :sections_json,
    updated_at = :updated_at
WHERE id = :id AND user_id = :user_id
SQL);

        $stmt->execute([
            'id' => $resumeId,
            'user_id' => $userId,
            'title' => mb_substr(trim((string) ($input['title'] ?? 'CV mới')), 0, 160),
            'template' => $template,
            'full_name' => mb_substr(trim((string) ($input['full_name'] ?? '')), 0, 160),
            'job_title' => mb_substr(trim((string) ($input['job_title'] ?? '')), 0, 160),
            'email' => mb_substr(trim((string) ($input['email'] ?? '')), 0, 190),
            'phone' => mb_substr(trim((string) ($input['phone'] ?? '')), 0, 50),
            'location' => mb_substr(trim((string) ($input['location'] ?? '')), 0, 190),
            'summary' => mb_substr(trim((string) ($input['summary'] ?? '')), 0, 2500),
            'sections_json' => json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now_string(),
        ]);
    }

    public static function delete(int $resumeId, int $userId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM resumes WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $resumeId, 'user_id' => $userId]);
    }

    public static function cloneResume(int $resumeId, int $userId): int
    {
        $source = self::requireOwned($resumeId, $userId);
        $pdo = Database::connection();
        $now = now_string();
        $params = [
            'user_id' => $userId,
            'title' => mb_substr((string) $source['title'] . ' - Bản sao', 0, 160),
            'template' => $source['template'],
            'full_name' => $source['full_name'],
            'job_title' => $source['job_title'],
            'email' => $source['email'],
            'phone' => $source['phone'],
            'location' => $source['location'],
            'summary' => $source['summary'],
            'sections_json' => $source['sections_json'],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $columns = '(user_id, title, template, full_name, job_title, email, phone, location, summary, sections_json, created_at, updated_at)';
        $values = '(:user_id, :title, :template, :full_name, :job_title, :email, :phone, :location, :summary, :sections_json, :created_at, :updated_at)';

        if (Database::driver() === 'pgsql') {
            $stmt = $pdo->prepare("INSERT INTO resumes {$columns} VALUES {$values} RETURNING id");
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        }

        $stmt = $pdo->prepare("INSERT INTO resumes {$columns} VALUES {$values}");
        $stmt->execute($params);
        return (int) $pdo->lastInsertId();
    }

    private static function sanitizeSections(string $json): array
    {
        $sections = decode_sections($json);
        $rules = [
            'educations' => ['school' => 180, 'degree' => 180, 'start' => 50, 'end' => 50, 'description' => 1200],
            'experiences' => ['company' => 180, 'role' => 180, 'start' => 50, 'end' => 50, 'description' => 1600],
            'projects' => ['name' => 200, 'technologies' => 300, 'description' => 1800, 'url' => 500],
            'skills' => ['name' => 120],
            'certificates' => ['name' => 180, 'issuer' => 180, 'year' => 50],
            'languages' => ['name' => 120, 'level' => 120],
            'links' => ['label' => 120, 'url' => 500],
        ];

        $clean = [];
        foreach ($rules as $section => $fields) {
            $clean[$section] = [];
            foreach (array_slice($sections[$section] ?? [], 0, 30) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $row = [];
                foreach ($fields as $field => $maxLength) {
                    $row[$field] = mb_substr(trim((string) ($item[$field] ?? '')), 0, $maxLength);
                }
                if (implode('', $row) !== '') {
                    $clean[$section][] = $row;
                }
            }
        }

        return $clean;
    }
}
