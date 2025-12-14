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

// Set error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', 1);
