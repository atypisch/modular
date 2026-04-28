<?php
/**
 * Migration runner for update process – runs core PHP migrations after core update
 */

namespace Modular\Update;

class MigrationRunner {
    private \PDO $db;
    private string $migrationsDir;

    public function __construct(\PDO $db, ?string $migrationsDir = null) {
        $this->db = $db;
        $this->migrationsDir = $migrationsDir ?? (defined('CORE_PATH') ? CORE_PATH . '/migrations' : '');
    }

    /**
     * Run all pending PHP migrations in core/migrations
     */
    public function runPending(): array {
        $ran = [];
        if ($this->migrationsDir === '' || !is_dir($this->migrationsDir)) {
            return $ran;
        }
        require_once defined('CORE_PATH') ? CORE_PATH . '/classes/Migration.php' : dirname(__DIR__) . '/Migration.php';
        $migration = new \Modular\Migration($this->db);
        $files = glob($this->migrationsDir . '/*_*.php');
        sort($files);
        foreach ($files as $file) {
            $basename = basename($file);
            if (!preg_match('/^(\d+)_(.+)\.php$/', $basename, $m)) {
                continue;
            }
            $version = $m[1];
            $name = $m[2];
            if ($migration->hasRun($version)) {
                continue;
            }
            ob_start();
            try {
                require $file;
                $migration->recordMigration($version, $name);
                $ran[] = $version . '_' . $name;
            } finally {
                ob_end_clean();
            }
        }
        return $ran;
    }
}
