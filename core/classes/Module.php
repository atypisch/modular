<?php
/**
 * Module System
 * Handles module loading, activation, deactivation (WordPress-style plugins)
 * Supports both public modules (module.php header) and system modules (module.json)
 */

namespace Modular;

class Module {
    private $db;
    private $name;
    private $slug;
    private $version;
    private $description;
    private $author;
    private $author_url;
    private $module_url;
    private $text_domain;
    private $requires_php;
    private $requires_modular;
    private $path;
    private $active;
    private $settings;
    private $module_type; // 'system' or 'module'
    private $github_repo;

    public function __construct($db, $moduleData = []) {
        $this->db = $db;
        $this->name = $moduleData['name'] ?? '';
        $this->slug = $moduleData['slug'] ?? '';
        $this->version = $moduleData['version'] ?? '1.0.0';
        $this->description = $moduleData['description'] ?? '';
        $this->author = $moduleData['author'] ?? '';
        $this->author_url = $moduleData['author_url'] ?? '';
        $this->module_url = $moduleData['module_url'] ?? '';
        $this->text_domain = $moduleData['text_domain'] ?? '';
        $this->requires_php = $moduleData['requires_php'] ?? '';
        $this->requires_modular = $moduleData['requires_modular'] ?? '';
        $this->path = $moduleData['path'] ?? '';
        $this->active = isset($moduleData['active']) ? (bool)$moduleData['active'] : false;
        $this->settings = $moduleData['settings'] ?? [];
        $this->module_type = $moduleData['module_type'] ?? 'module';
        $this->github_repo = $moduleData['github_repo'] ?? '';
    }

    /**
     * Load a system module from module.json
     * System modules are always active and never appear in the admin module list.
     */
    public static function loadFromJson($db, $modulePath) {
        $jsonFile = $modulePath . '/module.json';
        if (!file_exists($jsonFile)) {
            return null;
        }

        $meta = @json_decode(@file_get_contents($jsonFile), true);
        if (!is_array($meta) || empty($meta['slug'])) {
            return null;
        }

        $moduleData = [
            'name'              => $meta['name'] ?? $meta['slug'],
            'slug'              => $meta['slug'],
            'version'           => $meta['version'] ?? '1.0.0',
            'description'       => $meta['description'] ?? '',
            'author'            => $meta['author'] ?? 'Modular CMS',
            'author_url'        => $meta['author_url'] ?? '',
            'module_url'        => $meta['module_url'] ?? '',
            'text_domain'       => $meta['text_domain'] ?? $meta['slug'],
            'requires_php'      => $meta['requires_php'] ?? '',
            'requires_modular'  => $meta['requires_modular'] ?? '',
            'path'              => $modulePath,
            'active'            => true, // system modules are always active
            'settings'          => [],
            'module_type'       => 'system',
            'github_repo'       => $meta['github_repo'] ?? '',
        ];

        // Register/update in DB so the system is aware of installed system modules
        try {
            $stmt = $db->prepare("SELECT id FROM modules WHERE slug = ? LIMIT 1");
            $stmt->execute([$moduleData['slug']]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                $stmt = $db->prepare("
                    UPDATE modules
                    SET name = ?, version = ?, description = ?, author = ?,
                        module_type = 'system', active = 1, updated_at = NOW()
                    WHERE slug = ?
                ");
                $stmt->execute([
                    $moduleData['name'],
                    $moduleData['version'],
                    $moduleData['description'],
                    $moduleData['author'],
                    $moduleData['slug'],
                ]);
            } else {
                self::createInDatabase($db, $moduleData);
            }
        } catch (\PDOException $e) {
            // Table doesn't exist yet — safe to ignore during install
            error_log("Modules table not found (system module registration skipped): " . $e->getMessage());
        }

        return new self($db, $moduleData);
    }

    /**
     * Load module from file (public modules — WordPress-style PHP header)
     */
    public static function loadFromFile($db, $modulePath) {
        $moduleFile = $modulePath . '/module.php';

        if (!file_exists($moduleFile)) {
            return null;
        }

        // Read module header
        $moduleData = self::parseModuleFile($moduleFile);
        $moduleData['path'] = $modulePath;
        $moduleData['module_type'] = 'module';

        // Check if module exists in database (only if table exists)
        try {
            $stmt = $db->prepare("SELECT * FROM modules WHERE slug = ?");
            $stmt->execute([$moduleData['slug']]);
            $dbData = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($dbData) {
                $moduleData['active'] = (bool)$dbData['active'];
                $moduleData['settings'] = json_decode($dbData['settings'] ?? '{}', true);
                $moduleData['module_type'] = $dbData['module_type'] ?? 'module';
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
            'github_repo' => '',
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
            (name, slug, version, description, author, author_url, module_url, text_domain,
             requires_php, requires_modular, active, module_type, settings, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $settings = json_encode($moduleData['settings'] ?? []);
        $moduleType = $moduleData['module_type'] ?? 'module';
        $active = $moduleType === 'system' ? 1 : 0;

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
            $active,
            $moduleType,
            $settings
        ]);
    }

    /**
     * Activate module (public modules only — system modules are always active)
     */
    public function activate() {
        if ($this->module_type === 'system') {
            return true; // system modules cannot be toggled
        }

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
     * Deactivate module (public modules only — system modules cannot be deactivated)
     */
    public function deactivate() {
        if ($this->module_type === 'system') {
            throw new \Exception("System modules cannot be deactivated.");
        }

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
     * Whether this is a system module
     */
    public function isSystemModule(): bool {
        return $this->module_type === 'system';
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
            // Strip leading comment-only lines
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
