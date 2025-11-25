<?php

namespace AnimaID\Database\Migrations;

use AnimaID\Database\Migration;

/**
 * Add token blacklist table for JWT revocation
 */
class AddTokenBlacklist extends Migration
{
    public function getName(): string
    {
        return '20251125000001_add_token_blacklist';
    }

    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS token_blacklist (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                revoked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                reason TEXT,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Create index for faster lookups
        $this->execute("
            CREATE INDEX IF NOT EXISTS idx_token_blacklist_token 
            ON token_blacklist(token)
        ");

        $this->execute("
            CREATE INDEX IF NOT EXISTS idx_token_blacklist_expires 
            ON token_blacklist(expires_at)
        ");
    }

    public function down(): void
    {
        $this->execute("DROP INDEX IF EXISTS idx_token_blacklist_expires");
        $this->execute("DROP INDEX IF EXISTS idx_token_blacklist_token");
        $this->execute("DROP TABLE IF EXISTS token_blacklist");
    }
}
