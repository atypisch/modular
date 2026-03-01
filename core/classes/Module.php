<?php
/**
 * Module System
 * Handles module loading, activation, deactivation (WordPress-style plugins)
 */

namespace Modular;

class Module {
    private $db;
    private $name;
    private $slug;
    private $version;
    private $description;
    private $author;
    private $path;
    private $active;
    private $settings;
    
    public function __construct($db, $moduleData = []) {
        $this->db = $db;
        $this->name = $moduleData['name'] ?? '';
        $this->slug = $moduleData['slug'] ?? '';
        $this->version = $moduleData['version'] ?? '1.0.0';
        $this->description = $moduleData['description'] ?? '';
        $this->author = $moduleData['author'] ?? '';
        $this->path = $moduleData['path'] ?? '';
        $this->active = isset($moduleData['active']) ? (bool)$moduleData['active'] : false;
        $this->settings = $moduleData['settings'] ?? [];
    }
    
    /**
     * Load module from file
     */
    public static function loadFromFile($db, $modulePath) {
        $moduleFile = $modulePath . '/module.php';
        
        if (!file_exists($moduleFile)) {
            return null;
        }
        
        // Read module header
        $moduleData = self::parseModuleFile($moduleFile);
        $moduleData['path'] = $modulePath;
        
        // Check if module exists in database (only if table exists)
        try {
            $stmt = $db->prepare("SELECT * FROM modules WHERE slug = ?");
            $stmt->execute([$moduleData['slug']]);
            $dbData = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($dbData) {
                $moduleData['active'] = (bool)$dbData['active'];
                $moduleData['settings'] = json_decode($dbData['settings'] ?? '{}', true);
            } else {
                // Create module in database
                $moduleData['active'] = false;
                $moduleData['settings'] = [];
                self::createInDatabase($db, $moduleData);
            }
        } catch (\PDOException $e) {
            // Table doesn't exist yet - use defaults
            $moduleData['active'] = false;
            $moduleData['settings'] = [];
            error_log("Modules table not found. Please run install script. Error: " . $e->getMessage());
        }
        
        return new self($db, $moduleData);
    }
    
    /**
     * Parse module.php file header (WordPress-style)
     */
    private static function parseModuleFile($file) {
        $defaultHeaders = [
            'Name' => 'Module Name',
            'ModuleURI' => 'Module URI',
            'Version' => 'Version',
            'Description' => 'Description',
            'Author' => 'Author',
            'AuthorURI' => 'Author URI',
            'TextDomain' => 'Text Domain',
            'RequiresPHP' => 'Requires PHP',
            'RequiresModular' => 'Requires Modular',
        ];
        
        $fileContent = file_get_contents($file);
        $moduleData = [
            'name' => '',
            'slug' => '',
            'version' => '1.0.0',
            'description' => '',
            'author' => '',
            'author_url' => '',
            'module_url' => '',
            'text_domain' => '',
            'requires_php' => '',
            'requires_modular' => '',
        ];
        
        // Extract headers from PHP comment block
        foreach ($defaultHeaders as $field => $pattern) {
            if (preg_match('/' . preg_quote($pattern) . ':\s*(.+)/i', $fileContent, $matches)) {
                $value = trim($matches[1]);
                switch ($field) {
                    case 'Name':
                        $moduleData['name'] = $value;
                        $moduleData['slug'] = sanitize_title($value);
                        break;
                    case 'ModuleURI':
                        $moduleData['module_url'] = $value;
                        break;
                    case 'Version':
                        $moduleData['version'] = $value;
                        break;
                    case 'Description':
                        $moduleData['description'] = $value;
                        break;
                    case 'Author':
                        $moduleData['author'] = $value;
                        break;
                    case 'AuthorURI':
                        $moduleData['author_url'] = $value;
                        break;
                    case 'TextDomain':
                        $moduleData['text_domain'] = $value;
                        break;
                    case 'RequiresPHP':
                        $moduleData['requires_php'] = $value;
                        break;
                    case 'RequiresModular':
                        $moduleData['requires_modular'] = $value;
                        break;
                }
            }
        }
        
        return $moduleData;
    }
    
    /**
     * Create module in database
     */
    private static function createInDatabase($db, $moduleData) {
        $stmt = $db->prepare("
            INSERT INTO modules 
            (name, slug, version, description, author, author_url, module_url, text_domain, requires_php, requires_modular, active, settings, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $settings = json_encode($moduleData['settings'] ?? []);
        
        $stmt->execute([
            $moduleData['name'],
            $moduleData['slug'],
            $moduleData['version'],
            $moduleData['description'],
            $moduleData['author'],
            $moduleData['author_url'] ?? '',
            $moduleData['module_url'] ?? '',
            $moduleData['text_domain'] ?? '',
            $moduleData['requires_php'] ?? '',
            $moduleData['requires_modular'] ?? '',
            0, // Not active by default
            $settings
        ]);
    }
    
    /**
     * Activate module
     */
    public function activate() {
        if ($this->active) {
            return true;
        }
        
        // Check if modules table exists
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'modules'");
            if ($stmt->rowCount() === 0) {
                throw new \Exception("Modules table does not exist. Please run the install script first.");
            }
        } catch (\PDOException $e) {
            throw new \Exception("Modules table does not exist. Please run the install script first.");
        }
        
        // Run activation hook
        Hooks::doAction('module_activate', $this->slug);
        
        // Load module file
        $moduleFile = $this->path . '/module.php';
        if (file_exists($moduleFile)) {
            require_once $moduleFile;
        }
        
        // Update database
        try {
            $stmt = $this->db->prepare("UPDATE modules SET active = 1, updated_at = NOW() WHERE slug = ?");
            $stmt->execute([$this->slug]);
        } catch (\PDOException $e) {
            throw new \Exception("Failed to update module in database: " . $e->getMessage());
        }
        
        $this->active = true;
        
        // Run migrations if they exist
        $migrationsDir = $this->path . '/migrations';
        if (is_dir($migrationsDir)) {
            $this->runMigrations($migrationsDir);
        }
        
        return true;
    }
    
    /**
     * Deactivate module
     */
    public function deactivate() {
        if (!$this->active) {
            return true;
        }
        
        // Check if modules table exists
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'modules'");
            if ($stmt->rowCount() === 0) {
                throw new \Exception("Modules table does not exist. Please run the install script first.");
            }
        } catch (\PDOException $e) {
            throw new \Exception("Modules table does not exist. Please run the install script first.");
        }
        
        // Run deactivation hook
        Hooks::doAction('module_deactivate', $this->slug);
        
        // Update database
        try {
            $stmt = $this->db->prepare("UPDATE modules SET active = 0, updated_at = NOW() WHERE slug = ?");
            $stmt->execute([$this->slug]);
        } catch (\PDOException $e) {
            throw new \Exception("Failed to update module in database: " . $e->getMessage());
        }
        
        $this->active = false;
        
        return true;
    }
    
    /**
     * Run module migrations (PHP and SQL files in migrations/)
     */
    private function runMigrations($migrationsDir) {
        $phpMigrations = glob($migrationsDir . '/*.php');
        sort($phpMigrations);
        foreach ($phpMigrations as $migration) {
            require_once $migration;
        }

        $sqlMigrations = glob($migrationsDir . '/*.sql');
        sort($sqlMigrations);
        foreach ($sqlMigrations as $migration) {
            $this->runSqlFile($migration);
        }
    }

    /**
     * Execute a SQL migration file (multiple statements split by ;\n)
     */
    private function runSqlFile($filePath) {
        $sql = file_get_contents($filePath);
        if ($sql === false || trim($sql) === '') {
            return;
        }
        $statements = preg_split('/;\s*\n/', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            // Strip leading comment-only lines so statements that start with -- or # are still run
            $statement = preg_replace('/^(\s*(--[^\n]*|#[^\n]*)\s*\n)+/s', '', $statement);
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            try {
                $this->db->exec($statement);
            } catch (\PDOException $e) {
                throw new \RuntimeException("SQL migration failed: " . basename($filePath) . " – " . $e->getMessage(), 0, $e);
            }
        }
    }
    
    /**
     * Get module property
     */
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        return null;
    }
    
    /**
     * Check if module is active
     */
    public function isActive() {
        return $this->active;
    }
}

/**
 * Sanitize title (create slug)
 */
if (!function_exists('sanitize_title')) {
    function sanitize_title($title) {
        $title = strtolower($title);
        $title = preg_replace('/[^a-z0-9]+/', '-', $title);
        $title = trim($title, '-');
        return $title;
    }
}
