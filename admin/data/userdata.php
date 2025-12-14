<?php
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
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email_verification_token = ?");
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
            // Split full_name into first_name and last_name if provided
            $first_name = $data['first_name'] ?? '';
            $last_name = $data['last_name'] ?? '';
            
            if (isset($data['full_name']) && empty($first_name)) {
                $name_parts = explode(' ', $data['full_name'], 2);
                $first_name = $name_parts[0] ?? '';
                $last_name = $name_parts[1] ?? '';
            }
            
            $username = $data['username'] ?? $data['email'];
            
            $sql = "INSERT INTO {$this->table} (username, email, password, first_name, last_name, role, email_verification_token, email_verified, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $username,
                $data['email'],
                $data['password'],
                $first_name,
                $last_name,
                $data['role'] ?? 'editor',
                $data['email_verification_token'] ?? null,
                $data['email_verified'] ?? 0
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error in createUser: " . $e->getMessage());
            return false;
        }
    }

    public function confirmUser($token) {
        try {
            $sql = "UPDATE {$this->table} SET email_verified = 1, email_verification_token = NULL WHERE email_verification_token = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$token]);
        } catch (PDOException $e) {
            error_log("Database error in confirmUser: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser($id, $data) {
        try {
            $updates = [];
            $values = [];
            
            if (isset($data['username'])) {
                $updates[] = "username = ?";
                $values[] = $data['username'];
            }
            if (isset($data['email'])) {
                $updates[] = "email = ?";
                $values[] = $data['email'];
            }
            if (isset($data['password'])) {
                $updates[] = "password = ?";
                $values[] = $data['password'];
            }
            if (isset($data['first_name'])) {
                $updates[] = "first_name = ?";
                $values[] = $data['first_name'];
            }
            if (isset($data['last_name'])) {
                $updates[] = "last_name = ?";
                $values[] = $data['last_name'];
            }
            if (isset($data['role'])) {
                $updates[] = "role = ?";
                $values[] = $data['role'];
            }
            
            $updates[] = "updated_at = NOW()";
            $values[] = $id;
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
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
            // Check if table exists first
            $stmt = $this->db->query("SHOW TABLES LIKE '{$this->table}'");
            if ($stmt->rowCount() === 0) {
                error_log("Table {$this->table} does not exist");
                return false;
            }
            
            $stmt = $this->db->query("SELECT id, username, email, first_name, last_name, role, status, email_verified, created_at, updated_at FROM {$this->table} WHERE deleted_at IS NULL ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getAllUsers: " . $e->getMessage());
            // Check if it's a "table doesn't exist" error
            if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Base table or view not found") !== false) {
                error_log("Table {$this->table} does not exist");
            }
            return false;
        }
    }
}