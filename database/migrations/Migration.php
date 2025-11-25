<?php

namespace AnimaID\Database;

/**
 * Base Migration Class
 * All migrations should extend this class
 */
abstract class Migration
{
    protected \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Run the migration
     */
    abstract public function up(): void;

    /**
     * Reverse the migration
     */
    abstract public function down(): void;

    /**
     * Get migration name
     */
    abstract public function getName(): string;

    /**
     * Execute a SQL statement
     */
    protected function execute(string $sql): void
    {
        $this->db->exec($sql);
    }

    /**
     * Check if table exists
     */
    protected function tableExists(string $tableName): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM sqlite_master 
            WHERE type='table' AND name=?
        ");
        $stmt->execute([$tableName]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Check if column exists in table
     */
    protected function columnExists(string $tableName, string $columnName): bool
    {
        $stmt = $this->db->query("PRAGMA table_info($tableName)");
        $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            if ($column['name'] === $columnName) {
                return true;
            }
        }
        
        return false;
    }
}
