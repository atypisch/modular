<?php
require_once __DIR__ . '/core/config-check.php';

$configPath = __DIR__ . '/admin/config/config.php';
$hasConfig  = file_exists($configPath);
$needsSetup = true;

if ($hasConfig) {
    require_once $configPath;

    if (isset($GLOBALS['db_error'])) {
        $needsSetup = true;
    } elseif (!isset($GLOBALS['db'])) {
        $needsSetup = true;
    } else {
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

if (!$needsSetup) {
    require_once __DIR__ . '/public/index.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modular CMS</title>
    <link href="admin/assets/css/install.css" rel="stylesheet">
</head>
<body class="landing-page">
    <div class="landing-screen">
        <div class="landing-wrap">
            <div class="landing-card">
                <div class="landing-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h1 class="landing-title">Welcome to Modular CMS</h1>
                <p class="landing-text">It looks like Modular CMS hasn't been installed yet. Click below to start the installation process.</p>
                <a href="admin/setup/step1.php" class="btn-primary">Install Modular <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg></a>
            </div>
        </div>
    </div>
</body>
</html>