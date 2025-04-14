<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with success message
// Removed this line because session is already destroyed
// $_SESSION['success'] = 'You have been successfully logged out.';
header('Location: /modular/admin/auth/login.php');
exit;