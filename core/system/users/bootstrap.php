<?php
/**
 * System Module: Users — Bootstrap
 * Loaded automatically by ModuleManager before public modules.
 */

require_once __DIR__ . '/hooks.php';
require_once __DIR__ . '/routes.php';

do_action('system_module_loaded', 'users');
