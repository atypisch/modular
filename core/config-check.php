<?php
/**
 * Check if Modular CMS is installed.
 * We consider the system installed once the setup flow has completed
 * and written the installed.php marker file.
 * 
 * @return bool True if installed, false otherwise
 */
function isInstalled() {
    return file_exists(__DIR__ . '/../admin/config/installed.php');
}

/**
 * Check if all required directories exist and are writable
 * 
 * @return array Array of directory status checks
 */
function checkDirectoryDependencies() {
    $baseDir = __DIR__ . '/../';
    $directories = [
        'admin/config' => false,
        'admin/assets/css' => false,
        'admin/assets/js' => false,
        'public/uploads/media' => false,
        'public/uploads/cache' => false,
        'public/uploads/logs' => false,
    ];
    
    foreach ($directories as $dir => &$status) {
        $fullPath = $baseDir . $dir;
        if (!file_exists($fullPath)) {
            // Try to create the directory if it doesn't exist
            if (@mkdir($fullPath, 0755, true)) {
                $status = is_writable($fullPath);
            }
        } else {
            $status = is_writable($fullPath);
        }
    }
    
    return $directories;
}

/**
 * Get any error messages related to directory permissions
 * 
 * @return array Array of error messages
 */
function getDirectoryErrors() {
    $errors = [];
    $directories = checkDirectoryDependencies();
    
    foreach ($directories as $dir => $status) {
        if (!$status) {
            $errors[] = "Directory '$dir' is not writable or could not be created.";
        }
    }
    
    return $errors;
}

?>