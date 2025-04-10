<?php
// This script cleans up old data directories
$oldDirs = [
    'admin/auth/data',
    'admin/data',
    'data/old'
];

foreach ($oldDirs as $dir) {
    if (file_exists($dir)) {
        echo "Cleaning up: {$dir}\n";
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                array_map('unlink', glob($file . '/*'));
                rmdir($file);
            }
        }
        rmdir($dir);
    }
}

echo "Cleanup complete.\n";
