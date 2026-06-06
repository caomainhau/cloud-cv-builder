<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $databaseUrl = env_value('DATABASE_URL');
        if ($databaseUrl === null || trim($databaseUrl) === '') {
            $storageDir = dirname(__DIR__) . '/storage';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0775, true);
            }
            $dsn = 'sqlite:' . $storageDir . '/cloudcv.sqlite';
            $pdo = new PDO($dsn);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $parts = parse_url($databaseUrl);
            if ($parts === false || !isset($parts['host'])) {
                throw new RuntimeException('DATABASE_URL is invalid.');
            }

            $scheme = strtolower((string) ($parts['scheme'] ?? ''));
            if (!in_array($scheme, ['postgres', 'postgresql'], true)) {
                throw new RuntimeException('Only PostgreSQL DATABASE_URL values are supported in cloud mode.');
            }

            $host = $parts['host'];
            $port = (int) ($parts['port'] ?? 5432);
            $database = ltrim((string) ($parts['path'] ?? ''), '/');
            $user = rawurldecode((string) ($parts['user'] ?? ''));
            $password = rawurldecode((string) ($parts['pass'] ?? ''));
            $query = [];
            parse_str((string) ($parts['query'] ?? ''), $query);
            $sslmode = preg_replace('/[^a-z-]/i', '', (string) ($query['sslmode'] ?? 'prefer')) ?: 'prefer';

            $dsn = "pgsql:host={$host};port={$port};dbname={$database};sslmode={$sslmode}";
            $pdo = new PDO($dsn, $user, $password);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        self::$pdo = $pdo;
        return self::$pdo;
    }

    public static function driver(): string
    {
        return self::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}
