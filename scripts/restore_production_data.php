#!/usr/bin/env php
<?php

/**
 * AnimaID Production Data Restoration Script
 * 
 * This script safely restores production data from a backup database
 * while preserving the current schema and running necessary migrations.
 * 
 * Usage: php scripts/restore_production_data.php workfiles/animaid.db
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Check if backup file is provided
if ($argc < 2) {
    echo "Usage: php scripts/restore_production_data.php <backup_db_path>\n";
    echo "Example: php scripts/restore_production_data.php workfiles/animaid.db\n";
    exit(1);
}

$backupDbPath = $argv[1];

// Verify backup file exists
if (!file_exists($backupDbPath)) {
    echo "Error: Backup database file not found: {$backupDbPath}\n";
    exit(1);
}

// Define paths
$currentDbPath = __DIR__ . '/../database/animaid.db';
$backupCurrentDb = __DIR__ . '/../database/animaid.db.backup_' . date('Y-m-d_H-i-s');

echo "=========================================\n";
echo "AnimaID Production Data Restoration\n";
echo "=========================================\n\n";

echo "Backup database: {$backupDbPath}\n";
echo "Current database: {$currentDbPath}\n";
echo "Backup of current: {$backupCurrentDb}\n\n";

// Ask for confirmation
echo "This will:\n";
echo "1. Backup your current database to: {$backupCurrentDb}\n";
echo "2. Clear all data from the current database\n";
echo "3. Import data from the production backup\n";
echo "4. Preserve the current schema and migrations\n\n";

echo "Do you want to continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo "Restoration cancelled.\n";
    exit(0);
}

echo "\n";

try {
    // Step 1: Backup current database
    echo "[1/5] Backing up current database...\n";
    if (!copy($currentDbPath, $backupCurrentDb)) {
        throw new Exception("Failed to backup current database");
    }
    echo "✓ Current database backed up\n\n";

    // Step 2: Connect to both databases
    echo "[2/5] Connecting to databases...\n";
    $currentDb = new PDO("sqlite:{$currentDbPath}");
    $currentDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $backupDb = new PDO("sqlite:{$backupDbPath}");
    $backupDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to databases\n\n";

    // Step 3: Get list of tables to restore (exclude migrations and token_blacklist)
    echo "[3/5] Identifying tables to restore...\n";
    $excludeTables = ['migrations', 'sqlite_sequence'];
    
    $stmt = $backupDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $tablesToRestore = array_diff($tables, $excludeTables);
    
    echo "Tables to restore: " . count($tablesToRestore) . "\n";
    foreach ($tablesToRestore as $table) {
        echo "  - {$table}\n";
    }
    echo "\n";

    // Step 4: Clear current data (but keep schema)
    echo "[4/5] Clearing current data...\n";
    $currentDb->exec("PRAGMA foreign_keys = OFF");
    
    foreach ($tablesToRestore as $table) {
        // Check if table exists in current database
        $stmt = $currentDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
        if ($stmt->fetch()) {
            $currentDb->exec("DELETE FROM {$table}");
            echo "  ✓ Cleared {$table}\n";
        } else {
            echo "  ⚠ Table {$table} doesn't exist in current database (skipping)\n";
        }
    }
    
    $currentDb->exec("PRAGMA foreign_keys = ON");
    echo "✓ Data cleared\n\n";

    // Step 5: Import data from backup
    echo "[5/5] Importing data from backup...\n";
    $currentDb->exec("PRAGMA foreign_keys = OFF");
    
    foreach ($tablesToRestore as $table) {
        // Check if table exists in current database
        $stmt = $currentDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
        if (!$stmt->fetch()) {
            echo "  ⚠ Skipping {$table} (not in current schema)\n";
            continue;
        }
        
        // Get column names from current database
        $stmt = $currentDb->query("PRAGMA table_info({$table})");
        $currentColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
        
        // Get column names from backup database
        $stmt = $backupDb->query("PRAGMA table_info({$table})");
        $backupColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
        
        // Find common columns
        $commonColumns = array_intersect($currentColumns, $backupColumns);
        
        if (empty($commonColumns)) {
            echo "  ⚠ No common columns for {$table} (skipping)\n";
            continue;
        }
        
        $columnList = implode(', ', $commonColumns);
        $placeholders = implode(', ', array_fill(0, count($commonColumns), '?'));
        
        // Get data from backup
        $stmt = $backupDb->query("SELECT {$columnList} FROM {$table}");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            echo "  - {$table}: 0 rows\n";
            continue;
        }
        
        // Insert data into current database
        $insertStmt = $currentDb->prepare("INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})");
        
        $count = 0;
        foreach ($rows as $row) {
            $insertStmt->execute(array_values($row));
            $count++;
        }
        
        echo "  ✓ {$table}: {$count} rows imported\n";
    }
    
    $currentDb->exec("PRAGMA foreign_keys = ON");
    echo "\n✓ Data import completed\n\n";

    // Step 6: Verify restoration
    echo "Verifying restoration...\n";
    $stmt = $currentDb->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $currentDb->query("SELECT COUNT(*) as count FROM children");
    $childCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "  Users: {$userCount}\n";
    echo "  Children: {$childCount}\n";
    
    echo "\n=========================================\n";
    echo "Restoration Complete!\n";
    echo "=========================================\n\n";
    
    echo "Next steps:\n";
    echo "1. Verify the data by logging into the application\n";
    echo "2. If everything looks good, you can delete the backup: {$backupCurrentDb}\n";
    echo "3. If there are issues, restore from backup:\n";
    echo "   cp {$backupCurrentDb} {$currentDbPath}\n\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nRestoring from backup...\n";
    
    if (file_exists($backupCurrentDb)) {
        copy($backupCurrentDb, $currentDbPath);
        echo "✓ Database restored from backup\n";
    }
    
    exit(1);
}
