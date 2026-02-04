<?php
/**
 * Forms API Controller
 * Handles CRUD operations for forms
 */

namespace Modular\API;

class FormsController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function index() {
        $stmt = $this->db->query("
            SELECT f.*, 
                   COALESCE(u1.username, u1.email, 'Unknown') as created_by_name,
                   COALESCE(u2.username, u2.email, 'Unknown') as updated_by_name
            FROM forms f
            LEFT JOIN users u1 ON f.created_by = u1.id
            LEFT JOIN users u2 ON f.updated_by = u2.id
            ORDER BY f.name
        ");
        $forms = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($forms as &$form) {
            $form['fields'] = json_decode($form['fields'], true);
            $form['settings'] = json_decode($form['settings'], true);
            $form['spam_protection'] = json_decode($form['spam_protection'], true);
        }
        
        return $forms;
    }
    
    public function show($id) {
        $stmt = $this->db->prepare("
            SELECT f.*, 
                   COALESCE(u1.username, u1.email, 'Unknown') as created_by_name,
                   COALESCE(u2.username, u2.email, 'Unknown') as updated_by_name
            FROM forms f
            LEFT JOIN users u1 ON f.created_by = u1.id
            LEFT JOIN users u2 ON f.updated_by = u2.id
            WHERE f.id = ?
        ");
        $stmt->execute([$id]);
        $form = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$form) {
            return ['error' => 'Form not found', 'code' => 404];
        }
        
        $form['fields'] = json_decode($form['fields'], true);
        $form['settings'] = json_decode($form['settings'], true);
        $form['spam_protection'] = json_decode($form['spam_protection'], true);
        
        return $form;
    }
    
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        
        $name = $input['name'] ?? '';
        $slug = $input['slug'] ?? $this->slugify($name);
        $description = $input['description'] ?? '';
        $fields = is_string($input['fields'] ?? null) ? $input['fields'] : json_encode($input['fields'] ?? []);
        $settings = is_string($input['settings'] ?? null) ? $input['settings'] : json_encode($input['settings'] ?? []);
        $spamProtectionRaw = $input['spam_protection'] ?? ['enabled' => false];
        $spamProtection = is_bool($spamProtectionRaw) ? json_encode(['enabled' => $spamProtectionRaw]) : (is_string($spamProtectionRaw) ? $spamProtectionRaw : json_encode($spamProtectionRaw));
        
        if (empty($name) || empty($slug)) {
            return ['error' => 'Name and slug are required', 'code' => 400];
        }
        
        // Check if slug exists
        $stmt = $this->db->prepare("SELECT id FROM forms WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            return ['error' => 'Slug already exists', 'code' => 409];
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO forms 
            (name, slug, description, fields, settings, spam_protection, 
             created_by, updated_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $name, $slug, $description, $fields, $settings, $spamProtection,
            $userId, $userId
        ]);
        
        $id = $this->db->lastInsertId();
        return $this->show($id);
    }
    
    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        
        $existing = $this->show($id);
        if (isset($existing['error'])) {
            return $existing;
        }
        
        $name = $input['name'] ?? $existing['name'];
        $slug = $input['slug'] ?? $existing['slug'];
        $description = $input['description'] ?? $existing['description'];
        $fieldsIn = $input['fields'] ?? $existing['fields'];
        $fields = is_string($fieldsIn) ? $fieldsIn : json_encode($fieldsIn);
        $settingsIn = $input['settings'] ?? $existing['settings'];
        $settings = is_string($settingsIn) ? $settingsIn : json_encode($settingsIn);
        $spamProtectionRaw = $input['spam_protection'] ?? $existing['spam_protection'];
        $spamProtection = is_bool($spamProtectionRaw) ? json_encode(['enabled' => $spamProtectionRaw]) : (is_string($spamProtectionRaw) ? $spamProtectionRaw : json_encode($spamProtectionRaw));
        
        // Check slug uniqueness if changed
        if ($slug !== $existing['slug']) {
            $stmt = $this->db->prepare("SELECT id FROM forms WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
            if ($stmt->fetch()) {
                return ['error' => 'Slug already exists', 'code' => 409];
            }
        }
        
        $stmt = $this->db->prepare("
            UPDATE forms 
            SET name = ?, slug = ?, description = ?, fields = ?, settings = ?, 
                spam_protection = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name, $slug, $description, $fields, $settings, $spamProtection,
            $userId, $id
        ]);
        
        return $this->show($id);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM forms WHERE id = ?");
        $stmt->execute([$id]);
        
        return ['message' => 'Form deleted'];
    }
    
    public function submit($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $form = $this->show($id);
        if (isset($form['error'])) {
            return $form;
        }
        
        // Spam protection
        $spamProtection = $form['spam_protection'] ?? [];
        if ($spamProtection['enabled'] ?? false) {
            // Honeypot check
            if (isset($input['_honeypot']) && !empty($input['_honeypot'])) {
                return ['error' => 'Spam detected', 'code' => 403];
            }
            
            // Rate limiting (simplified - should use Redis in production)
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM form_submissions 
                WHERE form_id = ? AND ip_address = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$id, $ip]);
            $recentSubmissions = $stmt->fetchColumn();
            
            if ($recentSubmissions >= 10) {
                return ['error' => 'Too many submissions. Please try again later.', 'code' => 429];
            }
        }
        
        // Save submission
        $data = json_encode($input);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $this->db->prepare("
            INSERT INTO form_submissions 
            (form_id, data, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$id, $data, $ip, $userAgent]);
        
        return ['message' => 'Form submitted successfully', 'submission_id' => $this->db->lastInsertId()];
    }
    
    private function slugify($text) {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
}


