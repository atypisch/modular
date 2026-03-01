<?php
/**
 * Maintenance mode – enable/disable to block public access during updates
 */

namespace Modular\Update;

class MaintenanceModeService {
    private string $flagFile;

    public function __construct(?string $flagFile = null) {
        $base = defined('STORAGE_PATH') ? STORAGE_PATH : (defined('MODULAR_PATH') ? MODULAR_PATH . '/core/storage' : dirname(__DIR__, 2) . '/core/storage');
        $this->flagFile = $flagFile ?? ($base . '/.maintenance');
    }

    public function isEnabled(): bool {
        return is_file($this->flagFile);
    }

    /**
     * Enable maintenance mode. Ensures storage directory exists.
     */
    public function enable(): bool {
        $dir = dirname($this->flagFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return @file_put_contents($this->flagFile, date('c') . "\n") !== false;
    }

    /**
     * Disable maintenance mode
     */
    public function disable(): bool {
        if (!is_file($this->flagFile)) {
            return true;
        }
        return @unlink($this->flagFile);
    }
}
