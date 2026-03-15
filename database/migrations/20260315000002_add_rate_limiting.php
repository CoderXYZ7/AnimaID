<?php

namespace AnimaID\Database\Migrations;

use AnimaID\Database\Migration;

/**
 * Add Rate Limiting Migration
 * Creates the rate_limit_attempts table for tracking API request rates
 */
class AddRateLimiting extends Migration
{
    public function getName(): string
    {
        return '20260315000002_add_rate_limiting';
    }

    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS rate_limit_attempts (
                id INTEGER PRIMARY KEY,
                identifier VARCHAR(100) NOT NULL,
                endpoint VARCHAR(200),
                attempts INTEGER DEFAULT 1,
                window_start DATETIME,
                locked_until DATETIME
            )
        ");

        $this->execute("CREATE INDEX IF NOT EXISTS idx_rate_limit_identifier ON rate_limit_attempts(identifier)");
    }

    public function down(): void
    {
        $this->execute("DROP INDEX IF EXISTS idx_rate_limit_identifier");
        $this->execute("DROP TABLE IF EXISTS rate_limit_attempts");
    }
}
