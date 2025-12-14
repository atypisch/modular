<?php
/**
 * Component System
 * Handles component definitions, rendering, and storage
 */

namespace Modular;

class ComponentManager {
    private $db;
    private $components = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadComponentDefinitions();
    }
    
    private function loadComponentDefinitions() {
        // Load from database
        $stmt = $this->db->query("SELECT * FROM component_definitions ORDER BY `order`");
        $definitions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($definitions as $def) {
            $this->components[$def['slug']] = $def;
        }
    }
    
    /**
     * Register a component type
     */
    public function registerComponent($type, $config) {
        $this->components[$type] = $config;
    }
    
    /**
     * Get component definition
     */
    public function getComponent($slug) {
        return $this->components[$slug] ?? null;
    }
    
    /**
     * Render a component
     */
    public function render($componentData) {
        if (!isset($componentData['type'])) {
            return '';
        }
        
        $component = $this->getComponent($componentData['type']);
        if (!$component) {
            return '';
        }
        
        // Apply filters
        $componentData = Hooks::applyFilters('component_data', $componentData, $component);
        
        // Render based on type
        $output = Hooks::applyFilters('render_component', '', $componentData, $component);
        
        if (empty($output)) {
            $output = $this->defaultRender($componentData, $component);
        }
        
        return $output;
    }
    
    private function defaultRender($data, $component) {
        $schema = json_decode($component['schema'], true);
        $html = '<div class="component component-' . $component['slug'] . '">';
        
        foreach ($schema['fields'] ?? [] as $field) {
            $value = $data['data'][$field['name']] ?? '';
            $html .= $this->renderField($field, $value);
        }
        
        $html .= '</div>';
        return $html;
    }
    
    private function renderField($field, $value) {
        switch ($field['type']) {
            case 'text':
            case 'textarea':
                return '<div class="field-' . $field['name'] . '">' . htmlspecialchars($value) . '</div>';
            case 'wysiwyg':
                return '<div class="field-' . $field['name'] . '">' . $value . '</div>';
            case 'image':
                return '<img src="' . htmlspecialchars($value) . '" alt="' . htmlspecialchars($field['label'] ?? '') . '">';
            default:
                return '<div class="field-' . $field['name'] . '">' . htmlspecialchars($value) . '</div>';
        }
    }
    
    /**
     * Create component definition
     */
    public function createDefinition($postTypeId, $name, $slug, $type, $schema, $isFixed = true) {
        $stmt = $this->db->prepare("
            INSERT INTO component_definitions 
            (post_type_id, name, slug, type, `schema`, is_fixed, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $schemaJson = json_encode($schema);
        $stmt->execute([$postTypeId, $name, $slug, $type, $schemaJson, $isFixed ? 1 : 0]);
        
        return $this->db->lastInsertId();
    }
}

