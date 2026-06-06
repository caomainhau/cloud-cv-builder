<?php

declare(strict_types=1);

function env_value(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function load_dotenv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        $value = trim($value, "\"'");
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($messages) ? $messages : [];
}

function old_input(string $key, string $default = ''): string
{
    return e((string) ($_SESSION['_old'][$key] ?? $default));
}

function set_old_input(array $input): void
{
    $_SESSION['_old'] = $input;
}

function clear_old_input(): void
{
    unset($_SESSION['_old']);
}

function render(string $view, array $data = []): void
{
    $viewPath = dirname(__DIR__) . '/views/' . $view . '.php';
    if (!is_file($viewPath)) {
        throw new RuntimeException("View not found: {$view}");
    }

    extract($data, EXTR_SKIP);
    ob_start();
    require $viewPath;
    $content = ob_get_clean();
    require dirname(__DIR__) . '/views/layout.php';
}

function app_name(): string
{
    return env_value('APP_NAME', 'CloudCV Builder') ?? 'CloudCV Builder';
}

function now_string(): string
{
    return gmdate('Y-m-d H:i:s');
}

function decode_sections(?string $json): array
{
    $defaults = [
        'educations' => [],
        'experiences' => [],
        'projects' => [],
        'skills' => [],
        'certificates' => [],
        'languages' => [],
        'links' => [],
    ];

    if ($json === null || trim($json) === '') {
        return $defaults;
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return $defaults;
    }

    foreach ($defaults as $key => $value) {
        if (!isset($decoded[$key]) || !is_array($decoded[$key])) {
            $decoded[$key] = [];
        }
    }

    return array_merge($defaults, $decoded);
}

function safe_url(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }

    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

function is_https_request(): bool
{
    $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwarded === 'https';
}
