<?php
/**
 * Actions API Controller
 */

namespace Modular\API;

class ActionsController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function index() {
        $stmt = $this->db->query("SELECT * FROM actions ORDER BY priority ASC, created_at DESC");
        $actions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($actions as &$action) {
            $action['trigger_config'] = json_decode($action['trigger_config'], true);
            $action['action_config'] = json_decode($action['action_config'], true);
        }
        
        return $actions;
    }
    
    public function show($id) {
        $stmt = $this->db->prepare("SELECT * FROM actions WHERE id = ?");
        $stmt->execute([$id]);
        $action = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$action) {
            return ['error' => 'Action not found', 'code' => 404];
        }
        
        $action['trigger_config'] = json_decode($action['trigger_config'], true);
        $action['action_config'] = json_decode($action['action_config'], true);
        
        return $action;
    }
    
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $name = $input['name'] ?? '';
        $triggerType = $input['trigger_type'] ?? '';
        $triggerConfig = json_encode($input['trigger_config'] ?? []);
        $actionType = $input['action_type'] ?? '';
        $actionConfig = json_encode($input['action_config'] ?? []);
        $enabled = $input['enabled'] ?? 1;
        $priority = $input['priority'] ?? 10;
        
        if (empty($name) || empty($triggerType) || empty($actionType)) {
            return ['error' => 'Name, trigger type, and action type are required', 'code' => 400];
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO actions 
            (name, trigger_type, trigger_config, action_type, action_config, enabled, priority, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $name, $triggerType, $triggerConfig, $actionType, $actionConfig, $enabled, $priority
        ]);
        
        $id = $this->db->lastInsertId();
        return $this->show($id);
    }
    
    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $existing = $this->show($id);
        if (isset($existing['error'])) {
            return $existing;
        }
        
        $name = $input['name'] ?? $existing['name'];
        $triggerType = $input['trigger_type'] ?? $existing['trigger_type'];
        $triggerConfig = json_encode($input['trigger_config'] ?? $existing['trigger_config']);
        $actionType = $input['action_type'] ?? $existing['action_type'];
        $actionConfig = json_encode($input['action_config'] ?? $existing['action_config']);
        $enabled = $input['enabled'] ?? $existing['enabled'];
        $priority = $input['priority'] ?? $existing['priority'];
        
        $stmt = $this->db->prepare("
            UPDATE actions 
            SET name = ?, trigger_type = ?, trigger_config = ?, action_type = ?, 
                action_config = ?, enabled = ?, priority = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name, $triggerType, $triggerConfig, $actionType, $actionConfig, $enabled, $priority, $id
        ]);
        
        return $this->show($id);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM actions WHERE id = ?");
        $stmt->execute([$id]);
        
        return ['message' => 'Action deleted'];
    }
}

