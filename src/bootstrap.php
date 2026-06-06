<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
load_dotenv(dirname(__DIR__) . '/.env');

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Migrator.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/ActivityLogger.php';
require_once __DIR__ . '/LoginThrottle.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/ResumeRepository.php';

$sessionName = env_value('SESSION_NAME', 'cloudcv_session') ?? 'cloudcv_session';
session_name($sessionName);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => is_https_request(),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (is_https_request() && env_value('APP_ENV', 'local') === 'production') {
    header('Strict-Transport-Security: max-age=31536000');
}

Migrator::migrate(Database::connection());
