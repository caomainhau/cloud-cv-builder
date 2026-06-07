<?php

declare(strict_types=1);

final class Migrator
{
    public static function migrate(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            self::migratePostgres($pdo);
        } else {
            self::migrateSqlite($pdo);
        }

        self::createIndexes($pdo);
    }

    private static function migratePostgres(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    session_version INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
)
SQL);

        $emailColumnWasMissing = !self::columnExists($pdo, 'users', 'email_verified_at');
        if ($emailColumnWasMissing) {
            $pdo->exec('ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL');
            $pdo->exec('UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL');
        }
        if (!self::columnExists($pdo, 'users', 'session_version')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN session_version INTEGER NOT NULL DEFAULT 1');
        }

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

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS resume_shares (
    id SERIAL PRIMARY KEY,
    resume_id INTEGER NOT NULL REFERENCES resumes(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    revoked_at TIMESTAMP NULL,
    last_viewed_at TIMESTAMP NULL,
    view_count INTEGER NOT NULL DEFAULT 0
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL,
    consumed_at TIMESTAMP NULL
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL,
    consumed_at TIMESTAMP NULL
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS mail_request_attempts (
    id SERIAL PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    subject_hash VARCHAR(64) NOT NULL,
    ip_address VARCHAR(64) NOT NULL,
    attempt_count INTEGER NOT NULL DEFAULT 0,
    window_started_at TIMESTAMP NOT NULL,
    blocked_until TIMESTAMP NULL,
    updated_at TIMESTAMP NOT NULL,
    UNIQUE(action, subject_hash, ip_address)
)
SQL);
    }

    private static function migrateSqlite(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    session_version INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
SQL);

        $emailColumnWasMissing = !self::columnExists($pdo, 'users', 'email_verified_at');
        if ($emailColumnWasMissing) {
            $pdo->exec('ALTER TABLE users ADD COLUMN email_verified_at TEXT NULL');
            $pdo->exec('UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL');
        }
        if (!self::columnExists($pdo, 'users', 'session_version')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN session_version INTEGER NOT NULL DEFAULT 1');
        }

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

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS resume_shares (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resume_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    expires_at TEXT NULL,
    created_at TEXT NOT NULL,
    revoked_at TEXT NULL,
    last_viewed_at TEXT NULL,
    view_count INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL,
    consumed_at TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL,
    consumed_at TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS mail_request_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action TEXT NOT NULL,
    subject_hash TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    attempt_count INTEGER NOT NULL DEFAULT 0,
    window_started_at TEXT NOT NULL,
    blocked_until TEXT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(action, subject_hash, ip_address)
)
SQL);
    }

    private static function createIndexes(PDO $pdo): void
    {
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_resumes_user_id ON resumes(user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON activity_logs(user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs(created_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_updated_at ON login_attempts(updated_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_resume_shares_resume_id ON resume_shares(resume_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_resume_shares_user_id ON resume_shares(user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_resume_shares_expires_at ON resume_shares(expires_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_email_verification_user_id ON email_verification_tokens(user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_email_verification_expires_at ON email_verification_tokens(expires_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_password_reset_user_id ON password_reset_tokens(user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_password_reset_expires_at ON password_reset_tokens(expires_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mail_attempts_updated_at ON mail_request_attempts(updated_at)');
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $stmt = $pdo->prepare(<<<'SQL'
SELECT 1
FROM information_schema.columns
WHERE table_schema = current_schema() AND table_name = :table_name AND column_name = :column_name
LIMIT 1
SQL);
            $stmt->execute(['table_name' => $table, 'column_name' => $column]);
            return (bool) $stmt->fetchColumn();
        }

        $stmt = $pdo->query('PRAGMA table_info(' . $table . ')');
        foreach ($stmt->fetchAll() as $row) {
            if (($row['name'] ?? null) === $column) {
                return true;
            }
        }
        return false;
    }
}
