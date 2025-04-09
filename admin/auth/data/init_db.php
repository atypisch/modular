<?php
require_once dirname(__FILE__) . '/../../data/init.php';

$userData = new userdata();

// Check if admin user exists
$admin = $userData->getUserByEmail('admin@modular.com');
if (!$admin) {
    // Create admin user
    $userData->createUser([
        'name' => 'Admin',
        'email' => 'marten@atypisch.nl',
        'password' => password_hash('password', PASSWORD_DEFAULT),
        'role' => 'admin'
    ]);
    echo "Admin user created successfully!\n";
} else {
    echo "Admin user already exists.\n";
} 