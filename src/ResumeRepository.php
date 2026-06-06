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
            ActivityLogger::log('authorization.denied', $userId, 'denied', ['resume_id' => $resumeId]);
            http_response_code(404);
            exit('Không tìm thấy CV hoặc bạn không có quyền truy cập.');
        }
        return $resume;
    }

    public static function create(int $userId, string $title): int
    {
        $resumeId = self::insert($userId, [
            'title' => $title,
            'template' => 'ats-simple',
        ]);
        ActivityLogger::log('resume.create', $userId, 'success', ['resume_id' => $resumeId]);
        return $resumeId;
    }

    public static function update(int $resumeId, int $userId, array $input, bool $logActivity = true): void
    {
        self::requireOwned($resumeId, $userId);
        $data = self::normalizeResumeInput($input);
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
            ...$data,
            'updated_at' => now_string(),
        ]);
        if ($logActivity) {
            ActivityLogger::log('resume.update', $userId, 'success', ['resume_id' => $resumeId]);
        }
    }

    public static function delete(int $resumeId, int $userId): void
    {
        self::requireOwned($resumeId, $userId);
        $stmt = Database::connection()->prepare('DELETE FROM resumes WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $resumeId, 'user_id' => $userId]);
        ActivityLogger::log('resume.delete', $userId, 'success', ['resume_id' => $resumeId]);
    }

    public static function cloneResume(int $resumeId, int $userId): int
    {
        $source = self::requireOwned($resumeId, $userId);
        $newResumeId = self::insert($userId, [
            'title' => (string) $source['title'] . ' - Bản sao',
            'template' => $source['template'],
            'full_name' => $source['full_name'],
            'job_title' => $source['job_title'],
            'email' => $source['email'],
            'phone' => $source['phone'],
            'location' => $source['location'],
            'summary' => $source['summary'],
            'sections_json' => $source['sections_json'],
        ]);
        ActivityLogger::log('resume.clone', $userId, 'success', [
            'source_resume_id' => $resumeId,
            'resume_id' => $newResumeId,
        ]);
        return $newResumeId;
    }

    public static function exportData(int $resumeId, int $userId): array
    {
        $resume = self::requireOwned($resumeId, $userId);
        ActivityLogger::log('resume.export', $userId, 'success', ['resume_id' => $resumeId]);

        return [
            'schema_version' => 1,
            'exported_at' => gmdate('c'),
            'application' => 'CloudCV Builder',
            'resume' => [
                'title' => (string) $resume['title'],
                'template' => (string) $resume['template'],
                'full_name' => (string) $resume['full_name'],
                'job_title' => (string) $resume['job_title'],
                'email' => (string) $resume['email'],
                'phone' => (string) $resume['phone'],
                'location' => (string) $resume['location'],
                'summary' => (string) $resume['summary'],
                'sections' => decode_sections((string) $resume['sections_json']),
            ],
        ];
    }

    public static function importData(int $userId, array $payload): int
    {
        if ((int) ($payload['schema_version'] ?? 0) !== 1) {
            throw new InvalidArgumentException('File JSON không đúng phiên bản được hỗ trợ.');
        }
        if (!isset($payload['resume']) || !is_array($payload['resume'])) {
            throw new InvalidArgumentException('File JSON không có dữ liệu CV hợp lệ.');
        }

        $input = $payload['resume'];
        $input['title'] = trim((string) ($input['title'] ?? 'CV nhập từ JSON')) . ' - Bản nhập';
        $input['sections_json'] = $input['sections'] ?? [];
        $resumeId = self::insert($userId, $input);
        ActivityLogger::log('resume.import', $userId, 'success', ['resume_id' => $resumeId]);
        return $resumeId;
    }

    public static function sanitizeSections(mixed $input): array
    {
        if (is_string($input)) {
            $sections = decode_sections($input);
        } elseif (is_array($input)) {
            $sections = array_merge(decode_sections('{}'), $input);
        } else {
            $sections = decode_sections('{}');
        }

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
            $items = is_array($sections[$section] ?? null) ? $sections[$section] : [];
            foreach (array_slice($items, 0, 30) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $row = [];
                foreach ($fields as $field => $maxLength) {
                    $row[$field] = mb_substr(trim((string) ($item[$field] ?? '')), 0, $maxLength);
                }
                $visible = filter_var($item['_visible'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $row['_visible'] = $visible ?? true;
                $content = $row;
                unset($content['_visible']);
                if (implode('', $content) !== '') {
                    $clean[$section][] = $row;
                }
            }
        }

        return $clean;
    }

    private static function insert(int $userId, array $input): int
    {
        $pdo = Database::connection();
        $data = self::normalizeResumeInput($input);
        $now = now_string();
        $params = [
            'user_id' => $userId,
            ...$data,
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

    private static function normalizeResumeInput(array $input): array
    {
        $title = trim((string) ($input['title'] ?? 'CV mới'));
        if ($title === '') {
            $title = 'CV mới';
        }
        $template = in_array(($input['template'] ?? ''), ['ats-simple', 'modern-minimal'], true)
            ? (string) $input['template']
            : 'ats-simple';
        $sectionsInput = $input['sections_json'] ?? $input['sections'] ?? '{}';

        return [
            'title' => mb_substr($title, 0, 160),
            'template' => $template,
            'full_name' => mb_substr(trim((string) ($input['full_name'] ?? '')), 0, 160),
            'job_title' => mb_substr(trim((string) ($input['job_title'] ?? '')), 0, 160),
            'email' => mb_substr(trim((string) ($input['email'] ?? '')), 0, 190),
            'phone' => mb_substr(trim((string) ($input['phone'] ?? '')), 0, 50),
            'location' => mb_substr(trim((string) ($input['location'] ?? '')), 0, 190),
            'summary' => mb_substr(trim((string) ($input['summary'] ?? '')), 0, 2500),
            'sections_json' => json_encode(self::sanitizeSections($sectionsInput), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }
}
