<?php

namespace AnimaID\Database\Migrations;

use AnimaID\Database\Migration;

/**
 * Add Indexes Migration
 * Adds database indexes for commonly queried columns to improve performance
 */
class AddIndexes extends Migration
{
    public function getName(): string
    {
        return '20260315000001_add_indexes';
    }

    public function up(): void
    {
        $this->execute("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_users_is_active ON users(is_active)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_sessions_token ON user_sessions(session_token)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_sessions_expires ON user_sessions(expires_at)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_blacklist_token ON token_blacklist(token)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_children_status ON children(status)");
        $this->execute("CREATE INDEX IF NOT EXISTS idx_calendar_dates ON calendar_events(start_date, end_date)");
    }

    public function down(): void
    {
        $this->execute("DROP INDEX IF EXISTS idx_users_email");
        $this->execute("DROP INDEX IF EXISTS idx_users_username");
        $this->execute("DROP INDEX IF EXISTS idx_users_is_active");
        $this->execute("DROP INDEX IF EXISTS idx_sessions_token");
        $this->execute("DROP INDEX IF EXISTS idx_sessions_expires");
        $this->execute("DROP INDEX IF EXISTS idx_blacklist_token");
        $this->execute("DROP INDEX IF EXISTS idx_children_status");
        $this->execute("DROP INDEX IF EXISTS idx_calendar_dates");
    }
}
