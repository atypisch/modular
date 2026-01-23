<?php
/**
 * Bootstrap file for Modular CMS
 * Defines important constants and includes core files
 */

// Note: Most paths are defined in config.php
if (!defined('CORE_PATH')) {
    define('CORE_PATH', MODULAR_PATH . '/core');
}

// Include core files
require_once CORE_PATH . '/config-check.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/admin.php';
require_once CORE_PATH . '/functions/hooks.php';

// Load core classes
require_once CORE_PATH . '/classes/Hooks.php';
require_once CORE_PATH . '/classes/Module.php';
require_once CORE_PATH . '/classes/ModuleManager.php';

// Initialize module manager and load active modules
// Only if modules table exists to prevent errors
if (isset($GLOBALS['db'])) {
    try {
        $stmt = $GLOBALS['db']->query("SHOW TABLES LIKE 'modules'");
        if ($stmt->rowCount() > 0) {
            $moduleManager = new \Modular\ModuleManager($GLOBALS['db']);
            $GLOBALS['module_manager'] = $moduleManager;
        }
    } catch (\PDOException $e) {
        // Modules table doesn't exist yet - that's okay
        error_log("Modules table not found. Module system will be available after installation.");
    }
}

// Set error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', 1);
