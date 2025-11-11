<?php

/**
 * Migration script to make week types global instead of per-animator
 * This moves from per-animator week types to global week types with per-animator availability
 */

require_once __DIR__ . '/src/Database.php';

echo "Starting week types migration...\n";

try {
    $db = Database::getInstance();

    // Step 1: Create new global week_types table
    echo "Creating global week_types table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS week_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        );
    ");

    // Step 2: Create animator_week_type_availability table
    echo "Creating animator_week_type_availability table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS animator_week_type_availability (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            animator_id INTEGER NOT NULL,
            week_type_id INTEGER NOT NULL,
            day_of_week INTEGER NOT NULL, -- 1=Monday, 7=Sunday
            start_time TIME,
            end_time TIME,
            is_available BOOLEAN DEFAULT 1,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (animator_id) REFERENCES animators(id) ON DELETE CASCADE,
            FOREIGN KEY (week_type_id) REFERENCES week_types(id) ON DELETE CASCADE,
            UNIQUE(animator_id, week_type_id, day_of_week)
        );
    ");

    // Step 3: Migrate existing data
    echo "Migrating existing week types...\n";

