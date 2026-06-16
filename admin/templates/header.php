<?php
/**
 * Auth-page layout (login / register / forgot / reset password).
 *
 * The admin itself is the Atelier Vue SPA; the legacy server-rendered admin and
 * its chrome (sidebar/toolbar) have been removed. This minimal Atelier shell only
 * wraps the pre-login auth screens. Each auth page sets $page_title + $content, then:
 *   require header.php;  echo $content;  require footer.php;
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
$page_title = $page_title ?? 'Modular CMS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . ' - Modular CMS'; ?></title>
    <link rel="icon" href="<?php echo ADMIN_URL; ?>/assets/images/favicon_modular.svg" type="image/svg+xml">
    <!-- Atelier design system only -->
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/assets/spa/styles.css">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/assets/spa/auth.css">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/assets/vendor/fontawesome/css/all.min.css">
</head>
<body class="auth-page">
