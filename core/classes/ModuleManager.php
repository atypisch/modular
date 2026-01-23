<?php
/**
 * Module Manager
 * Manages all modules (WordPress-style plugin manager)
 */

namespace Modular;

class ModuleManager {
    private $db;
    private $modulesDir;
    private $activeModules = [];
    private $modules = [];
    
    public function __construct($db, $modulesDir = null) {
        $this->db = $db;
        $this->modulesDir = $modulesDir ?? PUBLIC_PATH . '/modules';
        $this->loadModules();
        $this->loadActiveModules();
    }
    
    /**
     * Load all modules from directory
     */
    private function loadModules() {
        if (!is_dir($this->modulesDir)) {
            return;
        }
        
        $dirs = array_filter(glob($this->modulesDir . '/*'), 'is_dir');
        
        foreach ($dirs as $dir) {
            $module = Module::loadFromFile($this->db, $dir);
            if ($module) {
                $this->modules[$module->slug] = $module;
            }
        }
    }
    
    /**
     * Load active modules
     */
    private function loadActiveModules() {
        try {
            // Check if modules table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'modules'");
            if ($stmt->rowCount() === 0) {
                error_log("Modules table does not exist. Skipping active modules load.");
                return;
            }
            
            $stmt = $this->db->query("SELECT slug FROM modules WHERE active = 1");
            $activeSlugs = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            foreach ($activeSlugs as $slug) {
                if (isset($this->modules[$slug])) {
                    $this->activeModules[$slug] = $this->modules[$slug];
                    $this->loadModuleFile($this->modules[$slug]);
                }
            }
        } catch (\PDOException $e) {
            // Table doesn't exist - that's okay, just log it
            error_log("Modules table not found. Please run install script. Error: " . $e->getMessage());
        } catch (\Exception $e) {
            error_log("Error loading active modules: " . $e->getMessage());
        }
    }
    
    /**
     * Load module file
     */
    private function loadModuleFile($module) {
        $moduleFile = $module->path . '/module.php';
        if (file_exists($moduleFile)) {
            require_once $moduleFile;
        }
    }
    
    /**
     * Get all modules
     */
    public function getModules() {
        return $this->modules;
    }
    
    /**
     * Get active modules
     */
    public function getActiveModules() {
        return $this->activeModules;
    }
    
    /**
     * Get module by slug
     */
    public function getModule($slug) {
        return $this->modules[$slug] ?? null;
    }
    
    /**
     * Activate module
     */
    public function activateModule($slug) {
        $module = $this->getModule($slug);
        if (!$module) {
            throw new \Exception("Module not found: {$slug}");
        }
        
        $module->activate();
        $this->activeModules[$slug] = $module;
        
        return true;
    }
    
    /**
     * Deactivate module
     */
    public function deactivateModule($slug) {
        $module = $this->getModule($slug);
        if (!$module) {
            throw new \Exception("Module not found: {$slug}");
        }
        
        $module->deactivate();
        unset($this->activeModules[$slug]);
        
        return true;
    }
    
    /**
     * Check if module is active
     */
    public function isModuleActive($slug) {
        return isset($this->activeModules[$slug]);
    }
}

