<?php
// Initialize database tables

// Load required files
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/database.php';

try {
    // Get database connection
    $db = get_database_connection();
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/init_database.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map(
            'trim',
            explode(';', $sql)
        )
    );
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if (!$db->query($statement)) {
                throw new Exception("Error executing SQL: " . $db->error . "\nStatement: " . $statement);
            }
        }
    }
    
    echo "Database tables created successfully!\n";
    
    // Create default admin user if users table is empty
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Create default admin user
        $username = 'admin';
        $email = 'admin@example.com';
        $password = password_hash('admin', PASSWORD_DEFAULT); // Change this in production!
        $created_at = date('Y-m-d H:i:s');
        $updated_at = $created_at;
        
        $stmt = $db->prepare("
            INSERT INTO users 
            (username, email, password, role, status, created_at, updated_at)
            VALUES (?, ?, ?, 'admin', 'active', ?, ?)
        ");
        
        $stmt->bind_param('sssss', $username, $email, $password, $created_at, $updated_at);
        
        if ($stmt->execute()) {
            echo "Default admin user created!\n";
            echo "Username: admin\n";
            echo "Password: admin\n";
            echo "Please change these credentials immediately after first login!\n";
        } else {
            throw new Exception("Error creating admin user: " . $stmt->error);
        }
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
