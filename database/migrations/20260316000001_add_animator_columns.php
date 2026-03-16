<?php

namespace AnimaID\Database\Migrations;

use AnimaID\Database\Migration;

class AddAnimatorColumns extends Migration
{
    public function up(): void
    {
        // Add columns missing from initial schema
        $columns = [
            'birth_place'      => 'VARCHAR(255)',
            'fiscal_code'      => 'VARCHAR(50)',
            'city'             => 'VARCHAR(100)',
            'postal_code'      => 'VARCHAR(20)',
            'termination_date' => 'DATE',
            'notes'            => 'TEXT',
        ];

        foreach ($columns as $column => $type) {
            try {
                $this->execute("ALTER TABLE animators ADD COLUMN {$column} {$type}");
            } catch (\Exception $e) {
                // Column already exists — ignore
            }
        }

        // Children table — same missing columns
        $childColumns = [
            'birth_place'  => 'VARCHAR(255)',
            'fiscal_code'  => 'VARCHAR(50)',
            'city'         => 'VARCHAR(100)',
            'postal_code'  => 'VARCHAR(20)',
            'notes'        => 'TEXT',
        ];

        foreach ($childColumns as $column => $type) {
            try {
                $this->execute("ALTER TABLE children ADD COLUMN {$column} {$type}");
            } catch (\Exception $e) {
                // Column already exists — ignore
            }
        }
    }

    public function down(): void
    {
        // SQLite does not support DROP COLUMN in older versions; left as no-op
    }

    public function getName(): string
    {
        return 'AddAnimatorColumns';
    }
}
