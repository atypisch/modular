<?php
/**
 * Migration System
 * Handles database schema versioning and migrations
 */

namespace Modular;

class Migration {
    private $db;
    private $migrationsTable = 'schema_migrations';
    
    public function __construct($db) {
        $this->db = $db;
        $this->ensureMigrationsTable();
    }
    
    private function ensureMigrationsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                version VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->db->exec($sql);
        } catch (\PDOException $e) {
            // If table already exists, that's fine
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }
    
    public function getCurrentVersion() {
        $stmt = $this->db->query("SELECT MAX(version) as version FROM {$this->migrationsTable}");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['version'] ?? '0.0.0';
    }
    
    public function hasRun($version) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->migrationsTable} WHERE version = ?");
        $stmt->execute([$version]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function recordMigration($version, $name) {
        $stmt = $this->db->prepare("INSERT INTO {$this->migrationsTable} (version, name) VALUES (?, ?)");
        $stmt->execute([$version, $name]);
    }
    
    public function run($version, $name, $upSql, $downSql = null) {
        if ($this->hasRun($version)) {
            return false; // Already run
        }
        
        // DDL statements (CREATE TABLE, etc.) cause implicit commits in MySQL
        // So we don't use transactions for migrations
        try {
            // Execute up migration
            $statements = array_filter(array_map('trim', explode(';', $upSql)));
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $this->db->exec($statement);
                    } catch (\PDOException $e) {
                        // If it's a "table already exists" error, continue
                        if (strpos($e->getMessage(), 'already exists') !== false || 
                            strpos($e->getMessage(), 'Duplicate') !== false) {
                            continue;
                        }
                        throw $e;
                    }
                }
            }
            
            // Record migration (this should be safe even without transaction)
            $this->recordMigration($version, $name);
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Migration {$version} failed: " . $e->getMessage());
        }
    }
    
    public function rollback($version) {
        // Implementation for rollback if needed
        // Would require storing down SQL
    }
}

