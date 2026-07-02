<?php
/**
 * Update log – write to storage/logs/updates.log
 */

namespace Modular\Update;

class UpdateLog {
    private static ?string $path = null;

    /** Rotate once the log passes this size so it cannot grow without bound. */
    private const MAX_BYTES = 5242880; // 5 MB

    public static function path(): string {
        if (self::$path !== null) {
            return self::$path;
        }
        $base = defined('STORAGE_PATH') ? STORAGE_PATH : (defined('MODULAR_PATH') ? MODULAR_PATH . '/core/storage' : '');
        $dir = $base . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        self::$path = $dir . '/updates.log';
        return self::$path;
    }

    public static function log(string $message, array $context = []): void {
        $line = date('Y-m-d H:i:s') . ' ' . $message;
        if (!empty($context)) {
            $line .= ' ' . json_encode($context);
        }
        $line .= "\n";
        $path = self::path();
        self::rotateIfNeeded($path);
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Keep a single previous generation (updates.log.1) and start a fresh log
     * once the current one passes MAX_BYTES, so the file never grows unbounded.
     */
    private static function rotateIfNeeded(string $path): void {
        if (!is_file($path)) {
            return;
        }
        $size = @filesize($path);
        if ($size === false || $size < self::MAX_BYTES) {
            return;
        }
        @rename($path, $path . '.1');
    }
}
