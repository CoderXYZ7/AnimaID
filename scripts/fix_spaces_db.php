<?php
require_once __DIR__ . '/../vendor/autoload.php';

use AnimaID\Config\ConfigManager;

echo "AnimaID Spaces Database Repair Tool\n";
echo "===================================\n\n";

try {
    $config = ConfigManager::getInstance();
    $dbPath = $config->get('database.file'); // ConfigManager returns absolute path already via __DIR__ concatenation in loadConfiguration

    
    if (!file_exists($dbPath)) {
        die("Database not found at $dbPath\n");
    }

    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking 'spaces' table columns...\n";
    $stmt = $pdo->query("PRAGMA table_info(spaces)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

    $missing = [];
    if (!in_array('parent_id', $columns)) $missing[] = 'parent_id';
    if (!in_array('type', $columns)) $missing[] = 'type';

    if (!in_array('color', $columns)) $missing[] = 'color';

    if (empty($missing)) {
        echo "✅ Database schema is correct. All columns exist.\n";
    } else {
        echo "⚠️  Missing columns detected: " . implode(', ', $missing) . "\n";
        echo "Attempting to repair...\n";

        if (in_array('parent_id', $missing)) {
            echo " - Adding 'parent_id' column...";
            $pdo->exec("ALTER TABLE spaces ADD COLUMN parent_id INTEGER DEFAULT NULL");
            echo " DONE.\n";
        }

        if (in_array('type', $missing)) {
            echo " - Adding 'type' column...";
            $pdo->exec("ALTER TABLE spaces ADD COLUMN type VARCHAR(50) DEFAULT 'space'");
            echo " DONE.\n";
        }

        if (in_array('color', $missing)) {
            echo " - Adding 'color' column...";
            $pdo->exec("ALTER TABLE spaces ADD COLUMN color VARCHAR(7) DEFAULT '#2563eb'");
            echo " DONE.\n";
        }
        
        echo "✅ Repair complete.\n";
    }
    
    // Also ensuring the migration is marked as run to prevent future duplications
    $migrationName = 'AddParentToSpaces';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
    $stmt->execute([$migrationName]);
    if ($stmt->fetchColumn() == 0) {
        echo "Marking migration '$migrationName' as executed in history...\n";
        $stmt = $pdo->prepare("INSERT INTO migrations (migration, executed_at) VALUES (?, datetime('now'))");
        $stmt->execute([$migrationName]);
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nVerification successful.\n";
