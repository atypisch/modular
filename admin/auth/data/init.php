<?php
require_once dirname(__DIR__, 4) . '/modular/data/userdata.php';

class DatabaseInitializer {
    private $db;
    private $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            confirmation_token VARCHAR(64) NULL,
            is_confirmed TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'sessions' => "CREATE TABLE IF NOT EXISTS sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    public function __construct() {
        global $db;
        $this->db = $db;
        
        // Only initialize tables if we have a valid database connection
        if ($this->db !== null) {
            $this->initializeTables();
        }
    }

    private function initializeTables() {
        foreach ($this->tables as $table => $sql) {
            try {
                if (!$this->db->query($sql)) {
                    error_log("Failed to create table $table: " . $this->db->error);
                }
            } catch (Exception $e) {
                error_log("Error creating table $table: " . $e->getMessage());
            }
        }
    }
}

// Initialize the database when this file is included
new DatabaseInitializer();
