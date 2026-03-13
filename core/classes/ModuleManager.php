<?php
/**
 * Module Manager
 * Manages all modules (WordPress-style plugin manager)
 *
 * Loading order:
 *   1. System modules  (/core/system/)   — always loaded, never shown in admin UI
 *   2. Public modules  (/public/modules/) — user-managed, shown in admin UI
 *
 * System modules auto-bootstrap via bootstrap.php and are always active.
 * Public modules require explicit activation via the admin UI / DB.
 */

namespace Modular;

class ModuleManager {
    private $db;
    private $modulesDir;       // /public/modules
    private $systemModulesDir; // /core/system
    private $activeModules  = [];
    private $modules        = []; // public modules
    private $systemModules  = []; // system modules

    public function __construct($db, $modulesDir = null, $systemModulesDir = null) {
        $this->db = $db;
        $this->modulesDir       = $modulesDir       ?? PUBLIC_PATH . '/modules';
        $this->systemModulesDir = $systemModulesDir ?? CORE_PATH   . '/system';

        // Loading order: system → public discovery → public activation
        $this->loadSystemModules();
        $this->loadModules();
        $this->loadActiveModules();
    }

    // -------------------------------------------------------------------------
    // System modules
    // -------------------------------------------------------------------------

    /**
     * Load and bootstrap all system modules from /core/system/.
     * System modules are always active; they are never listed in the admin UI.
     */
    private function loadSystemModules() {
        if (!is_dir($this->systemModulesDir)) {
            return;
        }

        $dirs = array_filter(glob($this->systemModulesDir . '/*'), 'is_dir');

        foreach ($dirs as $dir) {
            $module = Module::loadFromJson($this->db, $dir);
            if ($module) {
                $this->systemModules[$module->slug] = $module;
                $this->bootstrapSystemModule($module);
            }
        }

        do_action('system_modules_loaded');
    }

    /**
     * Execute bootstrap.php for a system module.
     */
    private function bootstrapSystemModule(Module $module) {
        $bootstrap = $module->path . '/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }

    // -------------------------------------------------------------------------
    // Public modules
    // -------------------------------------------------------------------------

    /**
     * Discover all public modules from /public/modules/.
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
     * Activate (load) all public modules that are marked active in the DB.
     */
    private function loadActiveModules() {
        try {
            // Check if modules table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'modules'");
            if ($stmt->rowCount() === 0) {
                error_log("Modules table does not exist. Skipping active modules load.");
                return;
            }

            $stmt = $this->db->query("SELECT slug FROM modules WHERE active = 1 AND module_type = 'module'");
            $activeSlugs = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($activeSlugs as $slug) {
                if (isset($this->modules[$slug])) {
                    $this->activeModules[$slug] = $this->modules[$slug];
                    $this->loadModuleFile($this->modules[$slug]);
                }
            }
        } catch (\PDOException $e) {
            error_log("Modules table not found. Please run install script. Error: " . $e->getMessage());
        } catch (\Exception $e) {
            error_log("Error loading active modules: " . $e->getMessage());
        }
    }

    /**
     * Load module.php for a public module.
     */
    private function loadModuleFile($module) {
        $moduleFile = $module->path . '/module.php';
        if (file_exists($moduleFile)) {
            require_once $moduleFile;
        }
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Get all public modules (excludes system modules).
     * Used by the admin module manager UI.
     */
    public function getModules() {
        return $this->modules;
    }

    /**
     * Get all system modules.
     */
    public function getSystemModules() {
        return $this->systemModules;
    }

    /**
     * Get active public modules.
     */
    public function getActiveModules() {
        return $this->activeModules;
    }

    /**
     * Get a public module by slug.
     */
    public function getModule($slug) {
        return $this->modules[$slug] ?? null;
    }

    /**
     * Get a system module by slug.
     */
    public function getSystemModule($slug) {
        return $this->systemModules[$slug] ?? null;
    }

    /**
     * Activate a public module.
     * System modules cannot be activated/deactivated via this method.
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
     * Deactivate a public module.
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
     * Check if a public module is active.
     */
    public function isModuleActive($slug) {
        return isset($this->activeModules[$slug]);
    }
}
