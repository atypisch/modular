<?php
/**
 * Plugin System
 * Handles plugin loading, activation, deactivation
 */

namespace Modular;

class PluginManager {
    private $db;
    private $pluginsDir;
    private $activePlugins = [];
    
    public function __construct($db, $pluginsDir = null) {
        $this->db = $db;
        $this->pluginsDir = $pluginsDir ?? MODULAR_PATH . '/modules';
        $this->loadActivePlugins();
    }
    
    private function loadActivePlugins() {
        $stmt = $this->db->query("SELECT name FROM settings WHERE option_name = 'active_plugins'");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            $this->activePlugins = json_decode($result['option_value'], true) ?: [];
        }
    }
    
    public function loadPlugins() {
        foreach ($this->activePlugins as $plugin) {
            $pluginFile = $this->pluginsDir . '/' . $plugin . '/module.php';
            if (file_exists($pluginFile)) {
                require_once $pluginFile;
            }
        }
    }
    
    public function activatePlugin($pluginName) {
        $pluginFile = $this->pluginsDir . '/' . $pluginName . '/module.php';
        if (!file_exists($pluginFile)) {
            throw new \Exception("Plugin {$pluginName} not found");
        }
        
        // Run activation hook
        Hooks::doAction('plugin_activate', $pluginName);
        
        // Load plugin
        require_once $pluginFile;
        
        // Add to active list
        if (!in_array($pluginName, $this->activePlugins)) {
            $this->activePlugins[] = $pluginName;
            $this->saveActivePlugins();
        }
        
        // Run migrations if plugin has them
        $migrationsDir = $this->pluginsDir . '/' . $pluginName . '/migrations';
        if (is_dir($migrationsDir)) {
            $this->runPluginMigrations($pluginName, $migrationsDir);
        }
    }
    
    public function deactivatePlugin($pluginName) {
        Hooks::doAction('plugin_deactivate', $pluginName);
        
        $this->activePlugins = array_filter($this->activePlugins, function($p) use ($pluginName) {
            return $p !== $pluginName;
        });
        $this->saveActivePlugins();
    }
    
    private function saveActivePlugins() {
        $value = json_encode($this->activePlugins);
        $stmt = $this->db->prepare("
            INSERT INTO settings (option_name, option_value, autoload, created_at, updated_at)
            VALUES ('active_plugins', ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE option_value = ?, updated_at = NOW()
        ");
        $stmt->execute([$value, $value]);
    }
    
    private function runPluginMigrations($pluginName, $migrationsDir) {
        // Plugin migration logic
        $migrations = glob($migrationsDir . '/*.php');
        foreach ($migrations as $migration) {
            require_once $migration;
        }
    }
    
    public function getPlugins() {
        $plugins = [];
        if (is_dir($this->pluginsDir)) {
            $dirs = array_filter(glob($this->pluginsDir . '/*'), 'is_dir');
            foreach ($dirs as $dir) {
                $pluginName = basename($dir);
                $pluginFile = $dir . '/module.php';
                if (file_exists($pluginFile)) {
                    $plugins[$pluginName] = [
                        'name' => $pluginName,
                        'active' => in_array($pluginName, $this->activePlugins),
                        'path' => $dir
                    ];
                }
            }
        }
        return $plugins;
    }
}


