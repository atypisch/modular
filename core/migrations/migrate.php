#!/usr/bin/env php
<?php
/**
 * Migration Runner
 * Executes pending migrations
 */

require_once __DIR__ . '/../../admin/config/config.php';
require_once __DIR__ . '/../classes/Migration.php';

use Modular\Migration;

if (!isset($GLOBALS['db'])) {
    die("Database connection not available\n");
}

$migration = new Migration($GLOBALS['db']);

$migrationsDir = __DIR__;
$migrations = glob($migrationsDir . '/*_*.php');
sort($migrations);

foreach ($migrations as $file) {
    $basename = basename($file);
    if (preg_match('/^(\d+)_(.+)\.php$/', $basename, $matches)) {
        $version = $matches[1];
        $name = $matches[2];
        
        if ($migration->hasRun($version)) {
            echo "Skipping {$version} ({$name}) - already executed.\n";
            continue;
        }
        
        echo "Running migration {$version} ({$name})...\n";
        require $file;
    }
}

echo "All migrations completed.\n";

