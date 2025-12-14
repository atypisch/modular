<?php
/**
 * API Authentication Controller
 * Handles login, registration, token management
 */

namespace Modular\API;

use Modular\Auth\JWT;

class AuthController {
    private $db;
    private $jwt;
    
    public function __construct($db) {
        $this->db = $db;
        $this->jwt = new JWT();
    }
    
    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            return ['error' => 'Email and password required', 'code' => 400];
        }
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password'])) {
            return ['error' => 'Invalid credentials', 'code' => 401];
        }
        
        if ($user['status'] !== 'active') {
            return ['error' => 'Account is not active', 'code' => 403];
        }
        
        // Update last login
        $this->db->prepare("UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?")
            ->execute([$_SERVER['REMOTE_ADDR'] ?? '', $user['id']]);
        
        // Generate token
        $token = $this->jwt->encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $this->getUserRole($user['id'])
        ]);
        
        // Set cookie
        setcookie('auth_token', $token, time() + 3600, '/', '', false, true);
        
        return [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'username' => $user['username'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ]
        ];
    }
    
    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $username = $input['username'] ?? $email;
        $first_name = $input['first_name'] ?? '';
        $last_name = $input['last_name'] ?? '';
        
        // Validation
        if (empty($email) || empty($password)) {
            return ['error' => 'Email and password required', 'code' => 400];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Invalid email format', 'code' => 400];
        }
        
        if (strlen($password) < 8) {
            return ['error' => 'Password must be at least 8 characters', 'code' => 400];
        }
        
        // Check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            return ['error' => 'User already exists', 'code' => 409];
        }
        
        // Create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password, first_name, last_name, role, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'user', NOW(), NOW())
        ");
        $stmt->execute([$username, $email, $hashedPassword, $first_name, $last_name]);
        
        $userId = $this->db->lastInsertId();
        
        // Assign default role
        $roleId = $this->db->query("SELECT id FROM roles WHERE name = 'user'")->fetchColumn();
        $this->db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")
            ->execute([$userId, $roleId]);
        
        return [
            'message' => 'User created successfully',
            'user_id' => $userId
        ];
    }
    
    public function forgotPassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        
        if (empty($email)) {
            return ['error' => 'Email required', 'code' => 400];
        }
        
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            $this->db->prepare("
                UPDATE users 
                SET reset_password_token = ?, reset_password_expires = ?
                WHERE id = ?
            ")->execute([$token, $expires, $user['id']]);
            
            // In production, send email here
            // For now, return token (remove in production!)
            return [
                'message' => 'Password reset token generated',
                'token' => $token // Remove in production!
            ];
        }
        
        // Don't reveal if user exists
        return ['message' => 'If user exists, reset email sent'];
    }
    
    public function resetPassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($token) || empty($password)) {
            return ['error' => 'Token and password required', 'code' => 400];
        }
        
        $stmt = $this->db->prepare("
            SELECT id FROM users 
            WHERE reset_password_token = ? 
            AND reset_password_expires > NOW()
            AND deleted_at IS NULL
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['error' => 'Invalid or expired token', 'code' => 400];
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $this->db->prepare("
            UPDATE users 
            SET password = ?, reset_password_token = NULL, reset_password_expires = NULL
            WHERE id = ?
        ")->execute([$hashedPassword, $user['id']]);
        
        return ['message' => 'Password reset successfully'];
    }
    
    private function getUserRole($userId) {
        $stmt = $this->db->prepare("
            SELECT r.name FROM roles r
            INNER JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = ?
            ORDER BY r.name = 'admin' DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 'user';
    }
}

