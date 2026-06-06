<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/helpers.php';
load_dotenv(dirname(__DIR__) . '/.env');
require_once dirname(__DIR__) . '/src/Database.php';
require_once dirname(__DIR__) . '/src/Migrator.php';

try {
    Migrator::migrate(Database::connection());
    fwrite(STDOUT, "Database migration completed.\n");
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration error: ' . $exception->getMessage() . "\n");
    exit(1);
}
