<?php

namespace AnimaID\Database\Migrations;

use AnimaID\Database\Migration;

class AddParentToSpaces extends Migration
{
    public function up(): void
    {
        // Check if parent_id column exists before adding it
        if (!$this->columnExists('spaces', 'parent_id')) {
            $this->db->exec("ALTER TABLE spaces ADD COLUMN parent_id INTEGER DEFAULT NULL");
        }
        
        // Check if type column exists
        if (!$this->columnExists('spaces', 'type')) {
            $this->db->exec("ALTER TABLE spaces ADD COLUMN type VARCHAR(50) DEFAULT 'space'"); // space, building, room
        }
    }

    public function down(): void
    {
        // SQLite does not support DROP COLUMN until recently, but for now we can ignore down
        // or recreate table if strictly needed. Since this is dev, we can skip complex down migration for now.
    }

    public function getName(): string
    {
        return 'AddParentToSpaces';
    }
}
