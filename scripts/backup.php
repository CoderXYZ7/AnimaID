#!/usr/bin/env php
<?php

/**
 * AnimaID Manual Backup Script
 * 
 * Creates timestamped backups of the database and uploads folder.
 * All backups are retained until manually deleted.
 * 
 * Usage: 
 *   php scripts/backup.php           # Backup database + uploads
 *   php scripts/backup.php --db      # Backup database only
 *   php scripts/backup.php --uploads # Backup uploads only
 *   php scripts/backup.php --list    # List all backups
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Define paths
define('ROOT_DIR', realpath(__DIR__ . '/..'));
define('DATABASE_PATH', ROOT_DIR . '/database/animaid.db');
define('UPLOADS_DIR', ROOT_DIR . '/uploads');
define('BACKUPS_DIR', ROOT_DIR . '/backups');

// Parse arguments
$args = array_slice($argv, 1);
$backupDb = true;
$backupUploads = true;
$listBackups = false;

if (in_array('--db', $args)) {
    $backupDb = true;
    $backupUploads = false;
} elseif (in_array('--uploads', $args)) {
    $backupDb = false;
    $backupUploads = true;
} elseif (in_array('--list', $args)) {
    $listBackups = true;
}

if (in_array('--help', $args) || in_array('-h', $args)) {
    echo "AnimaID Backup Script\n";
    echo "=====================\n\n";
    echo "Usage:\n";
    echo "  php scripts/backup.php           # Backup database + uploads\n";
    echo "  php scripts/backup.php --db      # Backup database only\n";
    echo "  php scripts/backup.php --uploads # Backup uploads only\n";
    echo "  php scripts/backup.php --list    # List all backups\n";
    echo "  php scripts/backup.php --help    # Show this help\n";
    exit(0);
}

// Ensure backups directory exists
if (!is_dir(BACKUPS_DIR)) {
    mkdir(BACKUPS_DIR, 0755, true);
    echo "✓ Created backups directory\n";
}

// List backups and exit
if ($listBackups) {
    listBackups();
    exit(0);
}

echo "=========================================\n";
echo "AnimaID Manual Backup\n";
echo "=========================================\n\n";

$timestamp = date('Y-m-d_H-i-s');
$backupResults = [];

// Backup database
if ($backupDb) {
    $result = backupDatabase($timestamp);
    $backupResults['database'] = $result;
}

// Backup uploads
if ($backupUploads) {
    $result = backupUploads($timestamp);
    $backupResults['uploads'] = $result;
}

// Summary
echo "\n=========================================\n";
echo "Backup Summary\n";
echo "=========================================\n\n";

foreach ($backupResults as $type => $result) {
    if ($result['success']) {
        echo "✓ {$type}: {$result['file']}\n";
        echo "  Size: " . formatBytes($result['size']) . "\n";
    } else {
        echo "✗ {$type}: {$result['error']}\n";
    }
}

echo "\nBackups location: " . BACKUPS_DIR . "\n";
echo "Timestamp: {$timestamp}\n\n";

// ============================================================================
// Functions
// ============================================================================

function backupDatabase(string $timestamp): array {
    echo "[Database] Starting backup...\n";
    
    if (!file_exists(DATABASE_PATH)) {
        return ['success' => false, 'error' => 'Database file not found: ' . DATABASE_PATH];
    }
    
    $backupFile = BACKUPS_DIR . "/animaid_db_{$timestamp}.db";
    
    // Use SQLite backup API for consistency
    try {
        $source = new PDO('sqlite:' . DATABASE_PATH);
        $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Run checkpoint to ensure WAL is flushed
        $source->exec('PRAGMA wal_checkpoint(FULL)');
        
        // Copy the database file
        if (!copy(DATABASE_PATH, $backupFile)) {
            return ['success' => false, 'error' => 'Failed to copy database file'];
        }
        
        $size = filesize($backupFile);
        echo "✓ Database backed up: {$backupFile}\n";
        
        return ['success' => true, 'file' => basename($backupFile), 'size' => $size];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function backupUploads(string $timestamp): array {
    echo "[Uploads] Starting backup...\n";
    
    if (!is_dir(UPLOADS_DIR)) {
        return ['success' => false, 'error' => 'Uploads directory not found: ' . UPLOADS_DIR];
    }
    
    // Check if uploads folder is empty
    $files = scandir(UPLOADS_DIR);
    $files = array_diff($files, ['.', '..', '.gitkeep', '.gitignore']);
    
    if (empty($files)) {
        echo "⚠ Uploads directory is empty, skipping...\n";
        return ['success' => true, 'file' => '(empty - skipped)', 'size' => 0];
    }
    
    $backupFile = BACKUPS_DIR . "/animaid_uploads_{$timestamp}.tar.gz";
    
    // Create tar.gz archive
    $command = sprintf(
        'tar -czf %s -C %s .',
        escapeshellarg($backupFile),
        escapeshellarg(UPLOADS_DIR)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0) {
        return ['success' => false, 'error' => 'Failed to create uploads archive'];
    }
    
    $size = filesize($backupFile);
    echo "✓ Uploads backed up: {$backupFile}\n";
    
    return ['success' => true, 'file' => basename($backupFile), 'size' => $size];
}

function listBackups(): void {
    echo "=========================================\n";
    echo "AnimaID Backups\n";
    echo "=========================================\n\n";
    
    if (!is_dir(BACKUPS_DIR)) {
        echo "No backups directory found.\n";
        return;
    }
    
    $files = scandir(BACKUPS_DIR);
    $files = array_diff($files, ['.', '..', '.gitkeep', '.gitignore']);
    
    if (empty($files)) {
        echo "No backups found.\n";
        return;
    }
    
    // Sort by modification time (newest first)
    usort($files, function($a, $b) {
        return filemtime(BACKUPS_DIR . '/' . $b) - filemtime(BACKUPS_DIR . '/' . $a);
    });
    
    $totalSize = 0;
    $dbBackups = [];
    $uploadBackups = [];
    
    foreach ($files as $file) {
        $path = BACKUPS_DIR . '/' . $file;
        $size = filesize($path);
        $totalSize += $size;
        $date = date('Y-m-d H:i:s', filemtime($path));
        
        $info = [
            'file' => $file,
            'size' => $size,
            'date' => $date
        ];
        
        if (strpos($file, '_db_') !== false) {
            $dbBackups[] = $info;
        } else {
            $uploadBackups[] = $info;
        }
    }
    
    if (!empty($dbBackups)) {
        echo "Database Backups:\n";
        echo str_repeat('-', 60) . "\n";
        foreach ($dbBackups as $backup) {
            printf("  %-45s %10s  %s\n", 
                $backup['file'], 
                formatBytes($backup['size']), 
                $backup['date']
            );
        }
        echo "\n";
    }
    
    if (!empty($uploadBackups)) {
        echo "Upload Backups:\n";
        echo str_repeat('-', 60) . "\n";
        foreach ($uploadBackups as $backup) {
            printf("  %-45s %10s  %s\n", 
                $backup['file'], 
                formatBytes($backup['size']), 
                $backup['date']
            );
        }
        echo "\n";
    }
    
    echo "Total: " . count($files) . " backup(s), " . formatBytes($totalSize) . "\n";
}

function formatBytes(int $bytes): string {
    if ($bytes === 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
