<?php

namespace AnimaID\Database\Migrations;

use AnimaID\Database\Migration;

class AddChildIdToParticipants extends Migration {
    public function getName(): string {
        return 'AddChildIdToEventParticipants';
    }

    public function up(): void {
        // Add child_id column to event_participants
        $this->db->exec("ALTER TABLE event_participants ADD COLUMN child_id INTEGER NULL REFERENCES children(id)");

        // Create index for better performance
        $this->db->exec("CREATE INDEX idx_event_participants_child_id ON event_participants(child_id)");

        // Attempt to link existing participants to children based on name and surname
        // This is a best-effort backfill
        $this->db->exec("
            UPDATE event_participants 
            SET child_id = (
                SELECT id 
                FROM children 
                WHERE children.first_name = event_participants.child_name 
                AND children.last_name = event_participants.child_surname
                LIMIT 1
            )
            WHERE child_id IS NULL
        ");
    }

    public function down(): void {
        // SQLite doesn't support DROP COLUMN easily in older versions, 
        // but for this app we can just ignore it or do a full table rebuild if needed.
        // For simplicity in this migration system, we'll leave it or would need a complex down.
        // Since this is an additive change, it's generally safe.
    }
}
