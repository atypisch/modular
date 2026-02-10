<?php
require_once __DIR__ . '/core/config-check.php';

$configPath = __DIR__ . '/admin/config/config.php';
$hasConfig  = file_exists($configPath);
$needsSetup = true;

if ($hasConfig) {
    // Try to load config and check DB / tables
    require_once $configPath;

    if (isset($GLOBALS['db_error'])) {
        $needsSetup = true;
    } elseif (!isset($GLOBALS['db'])) {
        $needsSetup = true;
    } else {
        // Check critical tables; if missing, we still consider it "not installed"
        require_once __DIR__ . '/core/functions/database.php';
        if (function_exists('check_database_tables')) {
            $status = check_database_tables();
            $critical = ['users', 'post_types'];
            $missingCritical = array_intersect($critical, $status['missing'] ?? []);
            $needsSetup = !empty($missingCritical) || !isInstalled();
        } else {
            $needsSetup = !isInstalled();
        }
    }
} else {
    $needsSetup = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modular CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <?php if ($needsSetup): ?>
        <div class="max-w-md w-full mx-4">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="mb-6">
                    <svg class="w-16 h-16 mx-auto text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Welcome to Modular CMS</h1>
                <p class="text-gray-600 mb-8">It looks like Modular CMS hasn't been installed yet. Click below to start the installation process.</p>
                <a href="admin/setup/step1.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    Install Modular
                    <svg class="ml-2 -mr-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="max-w-md w-full mx-4">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Welcome to Modular CMS</h1>
                <p class="text-gray-600 mb-6">This is a simple content management system built with PHP.</p>
                <a href="admin/" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    Go to Admin Panel
                    <svg class="ml-2 -mr-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 