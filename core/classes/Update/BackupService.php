<?php
/**
 * Backup service – backup /core, /public/modules, and store schema version before update
 */

namespace Modular\Update;

class BackupService {
    private string $backupPath;
    private \PDO $db;

    public function __construct(\PDO $db, ?string $backupPath = null) {
        $this->db = $db;
        $this->backupPath = $backupPath ?? (defined('UPDATE_BACKUP_PATH') ? UPDATE_BACKUP_PATH : (defined('MODULAR_PATH') ? MODULAR_PATH . '/core/storage/backups' : ''));
    }

    /**
     * Create a backup of core and modules into a versioned directory.
     * Returns path to backup dir or null on failure.
     */
    public function createBackup(string $label): ?string {
        if ($this->backupPath === '') {
            return null;
        }
        $dir = $this->backupPath . '/' . $label . '_' . date('Y-m-d_H-i-s');
        if (!is_dir($this->backupPath)) {
            @mkdir($this->backupPath, 0755, true);
        }
        if (!is_dir($this->backupPath) || !is_writable($this->backupPath)) {
            return null;
        }
        @mkdir($dir, 0755, true);
        if (!is_dir($dir)) {
            return null;
        }

        $modularPath = defined('MODULAR_PATH') ? MODULAR_PATH : dirname(__DIR__, 2);

        // Copy core (exclude large/unnecessary dirs if desired)
        $coreSrc = $modularPath . '/core';
        $coreDst = $dir . '/core';
        if (is_dir($coreSrc)) {
            $this->copyRecursive($coreSrc, $coreDst);
        }

        // Copy public/modules
        $modulesSrc = $modularPath . '/public/modules';
        $modulesDst = $dir . '/modules';
        if (is_dir($modulesSrc)) {
            $this->copyRecursive($modulesSrc, $modulesDst);
        }

        // Store schema version / DB version info
        $schemaVersion = $this->getSchemaVersion();
        @file_put_contents($dir . '/schema_version.txt', $schemaVersion);

        return $dir;
    }

    /**
     * Restore from a backup directory (restore core and modules from backup dir)
     */
    public function restoreFromBackup(string $backupDir): bool {
        if (!is_dir($backupDir)) {
            return false;
        }
        $modularPath = defined('MODULAR_PATH') ? MODULAR_PATH : dirname(__DIR__, 2);

        $coreSrc = $backupDir . '/core';
        $coreDst = $modularPath . '/core';
        if (is_dir($coreSrc)) {
            $this->copyRecursive($coreSrc, $coreDst);
        }

        $modulesSrc = $backupDir . '/modules';
        $modulesDst = $modularPath . '/public/modules';
        if (is_dir($modulesSrc)) {
            if (!is_dir($modulesDst)) {
                @mkdir($modulesDst, 0755, true);
            }
            $this->copyRecursive($modulesSrc, $modulesDst);
        }

        return true;
    }

    /**
     * Get current schema/migration version from database
     */
    public function getSchemaVersion(): string {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'schema_migrations'");
            if ($stmt->rowCount() === 0) {
                return 'none';
            }
            $stmt = $this->db->query("SELECT MAX(version) as v FROM schema_migrations");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['v'] ?? '0';
        } catch (\Throwable $e) {
            return 'error';
        }
    }

    private function copyRecursive(string $src, string $dst): void {
        if (!is_dir($src)) {
            return;
        }
        @mkdir($dst, 0755, true);
        $dir = new \DirectoryIterator($src);
        foreach ($dir as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            $srcPath = $src . '/' . $entry->getFilename();
            $dstPath = $dst . '/' . $entry->getFilename();
            if ($entry->isDir()) {
                $this->copyRecursive($srcPath, $dstPath);
            } else {
                @copy($srcPath, $dstPath);
            }
        }
    }
}
