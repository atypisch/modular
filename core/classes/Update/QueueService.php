<?php
/**
 * Queue service – process update/module jobs sequentially, prevent parallel execution
 */

namespace Modular\Update;

class QueueService {
    private \PDO $db;
    private string $lockFile;

    public function __construct(\PDO $db) {
        $this->db = $db;
        $base = defined('STORAGE_PATH') ? STORAGE_PATH : (defined('MODULAR_PATH') ? MODULAR_PATH . '/core/storage' : dirname(__DIR__, 2) . '/core/storage');
        $this->lockFile = $base . '/.update_queue_lock';
    }

    public function ensureTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS update_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(20) NOT NULL,
            target VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            started_at DATETIME DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            logs TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->exec($sql);
    }

    /**
     * Add a job to the queue
     */
    public function enqueue(string $type, string $target, string $action): int {
        $this->ensureTable();
        $stmt = $this->db->prepare("INSERT INTO update_queue (type, target, action, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$type, $target, $action]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Get next pending job (FIFO)
     */
    public function getNextPending(): ?array {
        $this->ensureTable();
        $stmt = $this->db->query("SELECT * FROM update_queue WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Mark job as running
     */
    public function markRunning(int $id): void {
        $this->db->prepare("UPDATE update_queue SET status = 'running', started_at = NOW() WHERE id = ?")->execute([$id]);
    }

    /**
     * Mark job as completed
     */
    public function markCompleted(int $id, ?string $logs = null): void {
        $stmt = $this->db->prepare("UPDATE update_queue SET status = 'completed', completed_at = NOW(), logs = ? WHERE id = ?");
        $stmt->execute([$logs, $id]);
    }

    /**
     * Mark job as failed
     */
    public function markFailed(int $id, string $error): void {
        $this->db->prepare("UPDATE update_queue SET status = 'failed', completed_at = NOW(), logs = ? WHERE id = ?")->execute([$error, $id]);
    }

    /**
     * Acquire lock for queue processing (prevent parallel)
     */
    public function acquireLock(): bool {
        $dir = dirname($this->lockFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $fp = @fopen($this->lockFile, 'c');
        if ($fp === false) return false;
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return false;
        }
        fwrite($fp, (string) getmypid());
        ftruncate($fp, strlen((string) getmypid()));
        $GLOBALS['_update_queue_lock_fp'] = $fp;
        return true;
    }

    /**
     * Release lock
     */
    public function releaseLock(): void {
        if (isset($GLOBALS['_update_queue_lock_fp'])) {
            flock($GLOBALS['_update_queue_lock_fp'], LOCK_UN);
            fclose($GLOBALS['_update_queue_lock_fp']);
            @unlink($this->lockFile);
            unset($GLOBALS['_update_queue_lock_fp']);
        }
    }

    /**
     * Get all jobs (for UI)
     */
    public function getAllJobs(int $limit = 50): array {
        $this->ensureTable();
        $stmt = $this->db->query("SELECT * FROM update_queue ORDER BY id DESC LIMIT " . (int) $limit);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get job by id
     */
    public function getJob(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM update_queue WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
