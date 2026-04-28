<?php
/**
 * Update & Module Management configuration (system config)
 *
 * Modes: production | staging | local
 */

if (!defined('MODULAR_PATH')) {
    return;
}

if (!defined('UPDATE_MODE')) {
    define('UPDATE_MODE', 'production');
}

if (!defined('UPDATE_CORE_GITHUB_REPO')) {
    define('UPDATE_CORE_GITHUB_REPO', 'atypisch/modular');
}

if (!defined('UPDATE_GITHUB_API_BASE')) {
    define('UPDATE_GITHUB_API_BASE', 'https://api.github.com');
}

// Optional personal access token for GitHub API (avoids rate limits and allows private repos).
// Read from environment variable MODULAR_UPDATE_GITHUB_TOKEN; leave empty for unauthenticated requests.
if (!defined('UPDATE_GITHUB_TOKEN')) {
    $envToken = getenv('MODULAR_UPDATE_GITHUB_TOKEN');
    define('UPDATE_GITHUB_TOKEN', $envToken !== false ? (string) $envToken : '');
}

if (!defined('UPDATE_TMP_PATH')) {
    define('UPDATE_TMP_PATH', MODULAR_PATH . '/updates/tmp');
}

if (!defined('UPDATE_BACKUP_PATH')) {
    define('UPDATE_BACKUP_PATH', MODULAR_PATH . '/core/storage/backups');
}

if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', MODULAR_PATH . '/core/storage');
}

if (!defined('UPDATE_SUPER_ADMIN_ROLE')) {
    define('UPDATE_SUPER_ADMIN_ROLE', 'admin');
}

if (!defined('UPDATE_RATE_LIMIT_PER_HOUR')) {
    define('UPDATE_RATE_LIMIT_PER_HOUR', 3);
}
