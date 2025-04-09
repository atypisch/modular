<?php
require_once __DIR__ . '/init.php';

class userdata {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = $GLOBALS['db'];
    }

    public function getUserByEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getUserByEmail: " . $e->getMessage());
            return false;
        }
    }

    public function getUserByConfirmationToken($token) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE confirmation_token = ?");
            $stmt->execute([$token]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getUserByConfirmationToken: " . $e->getMessage());
            return false;
        }
    }

    public function getUserById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getUserById: " . $e->getMessage());
            return false;
        }
    }

    public function createUser($data) {
        try {
            $sql = "INSERT INTO {$this->table} (name, email, password, role, confirmation_token, is_confirmed) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['password'],
                $data['role'] ?? 'user',
                $data['confirmation_token'] ?? null,
                $data['is_confirmed'] ?? 0
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error in createUser: " . $e->getMessage());
            return false;
        }
    }

    public function confirmUser($token) {
        try {
            $sql = "UPDATE {$this->table} SET is_confirmed = 1, confirmation_token = NULL WHERE confirmation_token = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$token]);
        } catch (PDOException $e) {
            error_log("Database error in confirmUser: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser($id, $data) {
        try {
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
        } catch (PDOException $e) {
            error_log("Database error in updateUser: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Database error in deleteUser: " . $e->getMessage());
            return false;
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getAllUsers: " . $e->getMessage());
            return false;
        }
    }
}