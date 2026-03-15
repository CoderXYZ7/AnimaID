<?php

namespace AnimaID\Database\Migrations;

use AnimaID\Database\Migration;

/**
 * Audit Log Migration
 * Adds a table to record user actions for security and compliance purposes.
 */
class AddAuditLog extends Migration
{
    public function getName(): string
    {
        return '20260315000003_add_audit_log';
    }

    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS audit_log (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id       INTEGER,
                action        VARCHAR(100) NOT NULL,
                resource_type VARCHAR(50),
                resource_id   INTEGER,
                ip_address    VARCHAR(45),
                user_agent    TEXT,
                request_data  TEXT,
                created_at    DATETIME DEFAULT (datetime('now'))
            )
        ");

        $this->execute("
            CREATE INDEX IF NOT EXISTS idx_audit_log_user_id
                ON audit_log (user_id)
        ");

        $this->execute("
            CREATE INDEX IF NOT EXISTS idx_audit_log_action
                ON audit_log (action)
        ");

        $this->execute("
            CREATE INDEX IF NOT EXISTS idx_audit_log_created_at
                ON audit_log (created_at)
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS audit_log");
    }
}
