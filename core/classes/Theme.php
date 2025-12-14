<?php
/**
 * Theme System
 * Handles theme loading and template rendering
 */

namespace Modular;

class Theme {
    private $db;
    private $activeTheme;
    private $themePath;
    private $themeUrl;
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadActiveTheme();
    }
    
    private function loadActiveTheme() {
        $stmt = $this->db->prepare("SELECT option_value FROM settings WHERE option_name = 'active_theme'");
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->activeTheme = $result ? $result['option_value'] : 'default';
        $this->themePath = PUBLIC_PATH . '/themes/' . $this->activeTheme;
        $this->themeUrl = '/modular/public/themes/' . $this->activeTheme;
    }
    
    /**
     * Render a template
     */
    public function render($template, $data = []) {
        $templateFile = $this->themePath . '/' . $template . '.php';
        
        if (!file_exists($templateFile)) {
            // Fallback to default theme
            $templateFile = PUBLIC_PATH . '/themes/default/' . $template . '.php';
        }
        
        if (!file_exists($templateFile)) {
            return '<p>Template not found: ' . htmlspecialchars($template) . '</p>';
        }
        
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        include $templateFile;
        return ob_get_clean();
    }
    
    /**
     * Get theme asset URL
     */
    public function asset($path) {
        return $this->themeUrl . '/' . ltrim($path, '/');
    }
    
    /**
     * Get theme setting
     */
    public function getSetting($key, $default = null) {
        $themeJson = $this->themePath . '/theme.json';
        if (file_exists($themeJson)) {
            $config = json_decode(file_get_contents($themeJson), true);
            return $config[$key] ?? $default;
        }
        return $default;
    }
    
    /**
     * Check if headless mode is enabled
     */
    public function isHeadless() {
        $stmt = $this->db->prepare("SELECT option_value FROM settings WHERE option_name = 'headless_mode'");
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result && $result['option_value'] === '1';
    }
    
    /**
     * Render post
     */
    public function renderPost($post) {
        if ($this->isHeadless()) {
            return json_encode($post);
        }
        
        // Determine template
        $template = 'single';
        if (isset($post['post_type_slug'])) {
            $template = 'single-' . $post['post_type_slug'];
        }
        
        return $this->render($template, ['post' => $post]);
    }
    
    /**
     * Render archive
     */
    public function renderArchive($postType, $posts) {
        if ($this->isHeadless()) {
            return json_encode($posts);
        }
        
        $template = 'archive';
        if ($postType) {
            $template = 'archive-' . $postType;
        }
        
        return $this->render($template, [
            'post_type' => $postType,
            'posts' => $posts
        ]);
    }
}
