<?php
require_once __DIR__ . '/../vendor/autoload.php';

use AnimaID\Config\ConfigManager;

echo "Diagnostic Tool for Spaces Module\n";
echo "=================================\n\n";

// 1. Check Files
echo "[1] Checking files...\n";
$requiredFiles = [
    'src/Repositories/SpaceRepository.php',
    'src/Services/SpaceService.php',
    'src/Controllers/SpaceController.php',
    'database/migrations/20251208000001_add_parent_to_spaces.php'
];

$missingFiles = 0;
foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/../' . $file)) {
        echo "  [OK] Found $file\n";
    } else {
        echo "  [FAIL] Missing $file\n";
        $missingFiles++;
    }
}
echo "\n";

// 2. Check Autoloader
echo "[2] Checking class loading...\n";
if (class_exists('AnimaID\Repositories\SpaceRepository')) {
    echo "  [OK] SpaceRepository class loaded.\n";
} else {
    echo "  [FAIL] SpaceRepository class NOT found. (Run 'composer dump-autoload')\n";
}
echo "\n";

// 3. Check Database
echo "[3] Checking database schema...\n";
try {
    $config = new ConfigManager(__DIR__ . '/../config/config.php');
    $dbPath = $config->get('database.path');
    
    if (!file_exists($dbPath)) {
        echo "  [FAIL] Database file not found at $dbPath\n";
        exit;
    }

    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check table
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='spaces'");
    if ($stmt->fetch()) {
        echo "  [OK] Table 'spaces' exists.\n";
        
        // Check columns
        $stmt = $pdo->query("PRAGMA table_info(spaces)");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        
        $requiredCols = ['parent_id', 'type'];
        foreach ($requiredCols as $col) {
            if (in_array($col, $columns)) {
                echo "  [OK] Column '$col' exists.\n";
            } else {
                echo "  [FAIL] Column '$col' is MISSING.\n";
            }
        }
    } else {
        echo "  [FAIL] Table 'spaces' does not exist.\n";
    }
    
    // Check migration status
    echo "\n  Checking migration history:\n";
    $stmt = $pdo->query("SELECT * FROM migrations ORDER BY executed_at DESC LIMIT 5");
    $migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($migrations as $m) {
        echo "    - " . $m['migration'] . " (Run at: " . $m['executed_at'] . ")\n";
    }

} catch (Exception $e) {
    echo "  [ERROR] Database connection failed: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";
