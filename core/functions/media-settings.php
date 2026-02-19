<?php
/**
 * Media image settings helpers.
 *
 * Stored in `settings` table under option_name = 'media_image_settings'.
 */

if (!function_exists('media_get_image_settings')) {
    function media_get_image_settings(): array {
        if (!isset($GLOBALS['db'])) {
            return [];
        }
        $db = $GLOBALS['db'];
        try {
            $stmt = $db->prepare("SELECT option_value FROM settings WHERE option_name = ? LIMIT 1");
            $stmt->execute(['media_image_settings']);
            $value = $stmt->fetchColumn();
            if ($value === false || $value === null || $value === '') {
                return [];
            }
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Exception $e) {
            return [];
        }
    }
}

if (!function_exists('media_save_image_settings')) {
    function media_save_image_settings(array $settings): bool {
        if (!isset($GLOBALS['db'])) {
            return false;
        }
        $db = $GLOBALS['db'];
        $json = json_encode($settings);
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("
                INSERT INTO settings (option_name, option_value, autoload, created_at, updated_at)
                VALUES (:name, :value, 1, :now, :now)
                ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), updated_at = VALUES(updated_at)
            ");
            return $stmt->execute([
                ':name' => 'media_image_settings',
                ':value' => $json,
                ':now' => $now,
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }
}

