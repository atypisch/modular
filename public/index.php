<?php
// Define paths
define('MODULAR_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);
define('THEMES_PATH', PUBLIC_PATH . '/themes');
define('DEFAULT_THEME', 'default');

// Set up basic content
$page_title = 'Home';
$content = '
    <div class="welcome-section">
        <h1>Welcome to Modular CMS</h1>
        <p>This is a simple content management system built with PHP.</p>
        <p>Features:</p>
        <ul>
            <li>Theme-based design</li>
            <li>Modular architecture</li>
            <li>Simple and clean interface</li>
        </ul>
    </div>
';
// Load the theme
require THEMES_PATH . '/' . DEFAULT_THEME . '/index.php'; 
