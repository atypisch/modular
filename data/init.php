<?php
require_once __DIR__ . '/userdata.php';

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
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'logs' => "CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    public function __construct() {
        $this->connect();
        $this->initializeDatabase();
    }

    private function connect() {
        $host = 'localhost';
        $dbname = 'modular';
        $port = 8889; // MAMP default port

        try {
            $this->db = new PDO(
                "mysql:host={$host};port={$port};dbname={$dbname}",
                'root',
                'root',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            // Make the database connection available globally
            $GLOBALS['db'] = $this->db;
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    private function initializeDatabase() {
        try {
            // Create tables in order (users first since others depend on it)
            $this->createTable('users');
            $this->createTable('sessions');
            $this->createTable('logs');
            
            // Log initialization after all tables are created
            $this->log("Database initialization completed");
        } catch (PDOException $e) {
            die("Database initialization failed: " . $e->getMessage());
        }
    }

    private function createTable($table) {
        if (!$this->tableExists($table)) {
            $sql = $this->tables[$table];
            $this->db->exec($sql);
            error_log("Created table: {$table}");
        }
    }

    private function tableExists($table) {
        $sql = "SELECT COUNT(*) FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$table]);
        return $stmt->fetchColumn() > 0;
    }

    private function log($message) {
        if ($this->tableExists('logs')) {
            $sql = "INSERT INTO logs (action, details) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['database_init', $message]);
        }
    }
}

// Initialize the database when this file is included
new DatabaseInitializer();
