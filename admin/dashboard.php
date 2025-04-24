<?php
require_once '/core/config-check.php';

if (!isInstalled()) {
    // If the CMS is not installed, redirect to the setup wizard.
    header('Location: /admin/setup/step1.php');
    exit;
}

// ... existing code ... 