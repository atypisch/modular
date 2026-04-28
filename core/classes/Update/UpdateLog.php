<?php
/**
 * Update log – write to storage/logs/updates.log
 */

namespace Modular\Update;

class UpdateLog {
    private static ?string $path = null;

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
        @file_put_contents(self::path(), $line, FILE_APPEND | LOCK_EX);
    }
}
