<?php
/**
 * Modular CMS - Core Initialization
 *
 * This file initializes the core functionality of the Modular CMS.
 */

/**
 * Core Initialization Functions
 */

// Check required constants
$required_constants = [
    'CORE_PATH',
    'MODULES_PATH',
    'THEMES_PATH',
    'ADMIN_PATH',
    'SITE_URL',
    'DEBUG_MODE'
];

foreach ($required_constants as $constant) {
    if (!defined($constant)) {
        die("Required constant {$constant} is not defined. Please check your configuration.");
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load core classes if they exist
$core_classes = [
    'Session',
    'Database',
    'Router',
    'Auth',
    'Module',
    'Theme'
];

foreach ($core_classes as $class) {
    $class_file = CORE_PATH . '/classes/' . $class . '.php';
    if (file_exists($class_file)) {
        require_once $class_file;
    }
}

// Initialize database if configuration exists and class exists
if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASSWORD') && class_exists('Database')) {
    try {
        $db = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            die('Database connection failed: ' . $e->getMessage());
        } else {
            die('Database connection failed. Please try again later.');
        }
    }
}

// Initialize authentication if class exists
if (class_exists('Auth') && isset($db)) {
    $auth = new Auth($db);
}

// Initialize router if class exists
if (class_exists('Router')) {
    $router = new Router();
}

// Load active modules
if (defined('ACTIVE_MODULES')) {
    $modules = unserialize(ACTIVE_MODULES);
    if (is_array($modules)) {
        foreach ($modules as $module) {
            $module_path = MODULES_PATH . '/' . $module;
            if (file_exists($module_path . '/init.php')) {
                require_once $module_path . '/init.php';
            }
        }
    }
}

// Load theme functions
if (defined('DEFAULT_THEME') && defined('THEMES_PATH')) {
    $theme_path = THEMES_PATH . '/' . DEFAULT_THEME;
    if (file_exists($theme_path . '/functions.php')) {
        require_once $theme_path . '/functions.php';
    }
}

/**
 * Debug function
 * @param mixed $data Data to debug
 * @param bool $die Whether to die after output
 */
function debug($data, $die = true) {
    if (!DEBUG_MODE) return;
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($die) die();
}

/**
 * Get the current URL
 * @return string Current URL
 */
function current_url() {
    if (!defined('SITE_URL')) return '';
    return SITE_URL . $_SERVER['REQUEST_URI'];
}

/**
 * Check if the current request is for the admin area
 * @return bool
 */
function is_admin() {
    return strpos($_SERVER['REQUEST_URI'], '/admin') !== false;
}

/**
 * Get a theme template path
 * @param string $template Template name
 * @return string Template path
 */
function get_template_path($template) {
    if (!defined('THEMES_PATH') || !defined('DEFAULT_THEME')) return '';
    return THEMES_PATH . '/' . DEFAULT_THEME . '/templates/' . $template . '.php';
}

/**
 * Load a template file
 * @param string $template Template name
 * @param array $data Data to pass to template
 * @return bool Whether the template was loaded
 */
function load_template($template, $data = []) {
    $template_file = get_template_path($template);
    if (!$template_file) return false;

    if ($data && is_array($data)) {
        extract($data);
    }

    if (file_exists($template_file)) {
        require $template_file;
        return true;
    }
    return false;
}
