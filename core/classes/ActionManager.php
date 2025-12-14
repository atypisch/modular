<?php
/**
 * Actions & Automation System
 * Handles workflow automation and action execution
 */

namespace Modular;

class ActionManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create an action
     */
    public function create($triggerType, $triggerConfig, $actionType, $actionConfig) {
        $stmt = $this->db->prepare("
            INSERT INTO actions 
            (name, trigger_type, trigger_config, action_type, action_config, enabled, priority, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 1, 10, NOW(), NOW())
        ");
        
        $name = $actionType . ' on ' . $triggerType;
        $triggerConfigJson = json_encode($triggerConfig);
        $actionConfigJson = json_encode($actionConfig);
        
        $stmt->execute([
            $name, $triggerType, $triggerConfigJson, $actionType, $actionConfigJson
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Execute actions for a trigger
     */
    public function executeTrigger($triggerType, $payload = []) {
        $stmt = $this->db->prepare("
            SELECT * FROM actions 
            WHERE trigger_type = ? AND enabled = 1
            ORDER BY priority ASC
        ");
        $stmt->execute([$triggerType]);
        $actions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($actions as $action) {
            $triggerConfig = json_decode($action['trigger_config'], true);
            
            // Check if trigger conditions match
            if ($this->matchesTrigger($triggerConfig, $payload)) {
                $this->executeAction($action, $payload);
            }
        }
    }
    
    /**
     * Check if trigger conditions match
     */
    private function matchesTrigger($triggerConfig, $payload) {
        // Simple matching - can be extended
        if (isset($triggerConfig['conditions'])) {
            foreach ($triggerConfig['conditions'] as $condition) {
                $field = $condition['field'] ?? null;
                $operator = $condition['operator'] ?? 'equals';
                $value = $condition['value'] ?? null;
                
                $payloadValue = $payload[$field] ?? null;
                
                switch ($operator) {
                    case 'equals':
                        if ($payloadValue != $value) return false;
                        break;
                    case 'not_equals':
                        if ($payloadValue == $value) return false;
                        break;
                    case 'contains':
                        if (strpos($payloadValue, $value) === false) return false;
                        break;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Execute an action
     */
    private function executeAction($action, $payload) {
        $actionType = $action['action_type'];
        $actionConfig = json_decode($action['action_config'], true);
        
        // Queue for async execution if needed
        if ($actionConfig['async'] ?? false) {
            $this->queueAction($action['id'], $payload);
            return;
        }
        
        // Execute immediately
        $this->runAction($actionType, $actionConfig, $payload);
    }
    
    /**
     * Run an action
     */
    private function runAction($actionType, $actionConfig, $payload) {
        switch ($actionType) {
            case 'send_email':
                $this->sendEmail($actionConfig, $payload);
                break;
            case 'webhook':
                $this->callWebhook($actionConfig, $payload);
                break;
            case 'create_post':
                $this->createPost($actionConfig, $payload);
                break;
            case 'update_status':
                $this->updateStatus($actionConfig, $payload);
                break;
            case 'add_tag':
                $this->addTag($actionConfig, $payload);
                break;
            case 'redirect':
                $this->redirect($actionConfig, $payload);
                break;
            case 'show_message':
                $this->showMessage($actionConfig, $payload);
                break;
            case 'run_hook':
                Hooks::doAction($actionConfig['hook'], $payload);
                break;
            default:
                // Allow plugins to handle custom actions
                Hooks::doAction('execute_action_' . $actionType, $actionConfig, $payload);
        }
    }
    
    private function sendEmail($config, $payload) {
        $to = $this->replacePlaceholders($config['to'] ?? '', $payload);
        $subject = $this->replacePlaceholders($config['subject'] ?? '', $payload);
        $body = $this->replacePlaceholders($config['body'] ?? '', $payload);
        
        // Use PHP mail or integrate with mailer
        mail($to, $subject, $body);
    }
    
    private function callWebhook($config, $payload) {
        $url = $config['url'] ?? '';
        if (empty($url)) return;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
    
    private function createPost($config, $payload) {
        // Create post via Post class
        $postData = [
            'post_type_id' => $config['post_type_id'] ?? 1,
            'title' => $this->replacePlaceholders($config['title'] ?? '', $payload),
            'status' => $config['status'] ?? 'draft',
            'component_data' => $config['component_data'] ?? []
        ];
        
        // This would use the Post class
        Hooks::doAction('action_create_post', $postData, $payload);
    }
    
    private function updateStatus($config, $payload) {
        $postId = $payload['post_id'] ?? null;
        $status = $config['status'] ?? 'published';
        
        if ($postId) {
            $stmt = $this->db->prepare("UPDATE posts SET status = ? WHERE id = ?");
            $stmt->execute([$status, $postId]);
        }
    }
    
    private function addTag($config, $payload) {
        $postId = $payload['post_id'] ?? null;
        $tag = $config['tag'] ?? '';
        
        if ($postId && $tag) {
            // Add taxonomy term
            Hooks::doAction('action_add_tag', $postId, $tag);
        }
    }
    
    private function redirect($config, $payload) {
        $url = $this->replacePlaceholders($config['url'] ?? '', $payload);
        if (!empty($url)) {
            header('Location: ' . $url);
            exit;
        }
    }
    
    private function showMessage($config, $payload) {
        $message = $this->replacePlaceholders($config['message'] ?? '', $payload);
        // Store in session for display
        $_SESSION['action_message'] = $message;
    }
    
    private function queueAction($actionId, $payload) {
        $stmt = $this->db->prepare("
            INSERT INTO action_queue 
            (action_id, payload, status, scheduled_at, created_at)
            VALUES (?, ?, 'pending', NOW(), NOW())
        ");
        $stmt->execute([$actionId, json_encode($payload)]);
    }
    
    private function replacePlaceholders($text, $payload) {
        foreach ($payload as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }
    
    /**
     * Process queued actions
     */
    public function processQueue($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT aq.*, a.action_type, a.action_config
            FROM action_queue aq
            INNER JOIN actions a ON aq.action_id = a.id
            WHERE aq.status = 'pending'
            AND aq.scheduled_at <= NOW()
            ORDER BY aq.created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $queued = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($queued as $item) {
            try {
                $payload = json_decode($item['payload'], true);
                $actionConfig = json_decode($item['action_config'], true);
                
                $this->runAction($item['action_type'], $actionConfig, $payload);
                
                // Mark as completed
                $updateStmt = $this->db->prepare("
                    UPDATE action_queue 
                    SET status = 'completed', executed_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$item['id']]);
            } catch (\Exception $e) {
                // Mark as failed
                $updateStmt = $this->db->prepare("
                    UPDATE action_queue 
                    SET status = 'failed', error_message = ?, attempts = attempts + 1
                    WHERE id = ?
                ");
                $updateStmt->execute([$e->getMessage(), $item['id']]);
            }
        }
    }
}

