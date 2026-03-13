<?php
/**
 * System Module: Activity — Bootstrap
 */

require_once __DIR__ . '/hooks.php';
require_once __DIR__ . '/routes.php';

do_action('system_module_loaded', 'activity');
