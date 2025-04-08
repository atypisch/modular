<?php
$host = 'localhost';
$port = 8889; // MAMP default port

// Try first set of credentials
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port}",
        'marten',
        'currents',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Connected with marten/currents credentials.\n";
} catch (PDOException $e) {
    // If first set fails, try root credentials
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port}",
            'root',
            'root',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "Connected with root/root credentials.\n";
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage() . "\n");
    }
}

try {
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS modular CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database 'modular' created or already exists.\n";

    // Select the database
    $pdo->exec("USE modular");

    echo "Database setup completed successfully!\n";
} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage() . "\n");
} 