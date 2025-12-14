<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load core configuration and functions
require_once __DIR__ . '/config/config.php';
require_once CORE_PATH . '/functions/database.php';
require_once CORE_PATH . '/functions/auth.php';

// Additional initialization can be added here
