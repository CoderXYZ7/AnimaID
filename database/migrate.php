<?php

namespace AnimaID\Database;

require_once __DIR__ . '/../vendor/autoload.php';

use AnimaID\Config\ConfigManager;

/**
 * Migration Runner
 * Manages database migrations
 */
class MigrationRunner
{
    private \PDO $db;
    private string $migrationsPath;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
        $this->migrationsPath = __DIR__ . '/migrations/';
        $this->ensureMigrationsTable();
    }

    /**
     * Create migrations tracking table if it doesn't exist
     */
    private function ensureMigrationsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) UNIQUE NOT NULL,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Run all pending migrations
     */
    public function migrate(): void
    {
        $migrations = $this->getPendingMigrations();

        if (empty($migrations)) {
            echo "No pending migrations.\n";
            return;
        }

        echo "Running " . count($migrations) . " migration(s)...\n";

        foreach ($migrations as $migrationFile) {
            $this->runMigration($migrationFile);
        }

        echo "All migrations completed successfully.\n";
    }

    /**
     * Rollback the last batch of migrations
     */
    public function rollback(int $steps = 1): void
    {
        $executed = $this->getExecutedMigrations();

        if (empty($executed)) {
            echo "No migrations to rollback.\n";
            return;
        }

        $toRollback = array_slice($executed, -$steps);

        echo "Rolling back " . count($toRollback) . " migration(s)...\n";

        foreach (array_reverse($toRollback) as $migration) {
            $this->rollbackMigration($migration);
        }

        echo "Rollback completed successfully.\n";
    }

    /**
     * Get migration status
     */
    public function status(): void
    {
        $all = $this->getAllMigrations();
        $executed = $this->getExecutedMigrations();

        echo "Migration Status:\n";
        echo str_repeat('-', 80) . "\n";
        printf("%-50s %s\n", "Migration", "Status");
        echo str_repeat('-', 80) . "\n";

        foreach ($all as $migration) {
            $status = in_array($migration, $executed) ? '✓ Executed' : '✗ Pending';
            printf("%-50s %s\n", $migration, $status);
        }

        echo str_repeat('-', 80) . "\n";
        echo "Total: " . count($all) . " migrations (" . count($executed) . " executed, " . (count($all) - count($executed)) . " pending)\n";
    }

    /**
     * Get all migration files
     */
    private function getAllMigrations(): array
    {
        $files = glob($this->migrationsPath . '*.php');
        $migrations = [];

        foreach ($files as $file) {
            $filename = basename($file);
            if ($filename !== 'Migration.php') {
                $migrations[] = $filename;
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Get executed migrations
     */
    private function getExecutedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY id");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get pending migrations
     */
    private function getPendingMigrations(): array
    {
        $all = $this->getAllMigrations();
        $executed = $this->getExecutedMigrations();
        return array_diff($all, $executed);
    }

    /**
     * Run a single migration
     */
    private function runMigration(string $migrationFile): void
    {
        // Load base Migration class first
        require_once $this->migrationsPath . 'Migration.php';
        
        // Then load the specific migration
        require_once $this->migrationsPath . $migrationFile;

        $className = $this->getClassNameFromFile($migrationFile);
        $migration = new $className($this->db);

        try {
            $this->db->beginTransaction();

            echo "Migrating: {$migration->getName()}...";
            $migration->up();

            // Record migration
            $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migrationFile]);

            $this->db->commit();
            echo " ✓\n";
        } catch (\Exception $e) {
            $this->db->rollBack();
            echo " ✗\n";
            throw new \Exception("Migration failed: " . $e->getMessage());
        }
    }

    /**
     * Rollback a single migration
     */
    private function rollbackMigration(string $migrationFile): void
    {
        // Load base Migration class first
        require_once $this->migrationsPath . 'Migration.php';
        
        // Then load the specific migration
        require_once $this->migrationsPath . $migrationFile;

        $className = $this->getClassNameFromFile($migrationFile);
        $migration = new $className($this->db);

        try {
            $this->db->beginTransaction();

            echo "Rolling back: {$migration->getName()}...";
            $migration->down();

            // Remove migration record
            $stmt = $this->db->prepare("DELETE FROM migrations WHERE migration = ?");
            $stmt->execute([$migrationFile]);

            $this->db->commit();
            echo " ✓\n";
        } catch (\Exception $e) {
            $this->db->rollBack();
            echo " ✗\n";
            throw new \Exception("Rollback failed: " . $e->getMessage());
        }
    }

    /**
     * Get class name from migration file
     */
    private function getClassNameFromFile(string $filename): string
    {
        // Remove .php extension and convert to class name
        $name = str_replace('.php', '', $filename);
        
        // Remove timestamp prefix (e.g., "20251125000001_")
        $name = preg_replace('/^\d+_/', '', $name);
        
        // Convert snake_case to PascalCase
        $parts = explode('_', $name);
        $className = implode('', array_map('ucfirst', $parts));
        
        return "AnimaID\\Database\\Migrations\\{$className}";
    }
}

// CLI interface
if (php_sapi_name() === 'cli') {
    $config = ConfigManager::getInstance();
    $dbFile = $config->get('database.file');

    if (!file_exists($dbFile)) {
        die("Database file not found. Please run database initialization first.\n");
    }

    $pdo = new \PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    $runner = new MigrationRunner($pdo);

    $command = $argv[1] ?? 'status';

    switch ($command) {
        case 'migrate':
        case 'up':
            $runner->migrate();
            break;

        case 'rollback':
        case 'down':
            $steps = isset($argv[2]) ? (int)$argv[2] : 1;
            $runner->rollback($steps);
            break;

        case 'status':
            $runner->status();
            break;

        default:
            echo "Usage: php migrate.php [command]\n";
            echo "Commands:\n";
            echo "  migrate|up     - Run all pending migrations\n";
            echo "  rollback|down  - Rollback last migration (optionally specify number of steps)\n";
            echo "  status         - Show migration status\n";
            break;
    }
}
