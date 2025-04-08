<?php
class UserData {
    private $db;
    private $table = 'users';

    public function __construct() {
        $host = 'localhost';
        $dbname = 'modular';
        $port = 8889; // MAMP default port

        // Try first set of credentials
        try {
            $this->db = new PDO(
                "mysql:host={$host};port={$port};dbname={$dbname}",
                'marten',
                'currents',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->createTable();
        } catch (PDOException $e) {
            // If first set fails, try root credentials
            try {
                $this->db = new PDO(
                    "mysql:host={$host};port={$port};dbname={$dbname}",
                    'root',
                    'root',
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $this->createTable();
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
    }

    private function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->exec($sql);
    }

    public function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createUser($data) {
        $sql = "INSERT INTO {$this->table} (name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['password'],
            $data['role'] ?? 'user'
        ]);
        return $this->db->lastInsertId();
    }

    public function updateUser($id, $data) {
        $sql = "UPDATE {$this->table} SET name = ?, email = ?, password = ?, role = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['password'],
            $data['role'] ?? 'user',
            $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function deleteUser($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function getAllUsers() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 