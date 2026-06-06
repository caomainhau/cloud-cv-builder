<?php

declare(strict_types=1);

final class Migrator
{
    public static function migrate(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
)
SQL);

            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS resumes (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(160) NOT NULL,
    template VARCHAR(50) NOT NULL DEFAULT 'ats-simple',
    full_name VARCHAR(160) NOT NULL DEFAULT '',
    job_title VARCHAR(160) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL DEFAULT '',
    phone VARCHAR(50) NOT NULL DEFAULT '',
    location VARCHAR(190) NOT NULL DEFAULT '',
    summary TEXT NOT NULL DEFAULT '',
    sections_json TEXT NOT NULL DEFAULT '{}',
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
)
SQL);

            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS activity_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(120) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'success',
    ip_address VARCHAR(64) NOT NULL DEFAULT '',
    user_agent VARCHAR(500) NOT NULL DEFAULT '',
    metadata_json TEXT NOT NULL DEFAULT '{}',
    created_at TIMESTAMP NOT NULL
)
SQL);

            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS login_attempts (
    id SERIAL PRIMARY KEY,
    email_hash VARCHAR(64) NOT NULL,
    ip_address VARCHAR(64) NOT NULL,
    attempt_count INTEGER NOT NULL DEFAULT 0,
    window_started_at TIMESTAMP NOT NULL,
    blocked_until TIMESTAMP NULL,
    updated_at TIMESTAMP NOT NULL,
    UNIQUE(email_hash, ip_address)
)
SQL);
        } else {
            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
SQL);

            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS resumes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    template TEXT NOT NULL DEFAULT 'ats-simple',
    full_name TEXT NOT NULL DEFAULT '',
    job_title TEXT NOT NULL DEFAULT '',
    email TEXT NOT NULL DEFAULT '',
    phone TEXT NOT NULL DEFAULT '',
    location TEXT NOT NULL DEFAULT '',
    summary TEXT NOT NULL DEFAULT '',
    sections_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
SQL);

            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,
    action TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'success',
    ip_address TEXT NOT NULL DEFAULT '',
    user_agent TEXT NOT NULL DEFAULT '',
    metadata_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)
SQL);

            $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email_hash TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    attempt_count INTEGER NOT NULL DEFAULT 0,
    window_started_at TEXT NOT NULL,
    blocked_until TEXT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(email_hash, ip_address)
)
SQL);
        }

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_resumes_user_id ON resumes(user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON activity_logs(user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs(created_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_updated_at ON login_attempts(updated_at)');
    }
}
