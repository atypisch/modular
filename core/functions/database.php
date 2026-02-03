<?php
/**
 * Database helper functions
 */

if (!function_exists('check_database_tables')) {
    /**
     * Check if required database tables exist
     * 
     * @return array ['exists' => array, 'missing' => array, 'all_exist' => bool, 'error' => string|null]
     */
    function check_database_tables() {
        if (!isset($GLOBALS['db'])) {
            return [
                'exists' => [],
                'missing' => [],
                'all_exist' => false,
                'error' => 'Database connection not available'
            ];
        }
        
        // Ensure DB_NAME is defined
        if (!defined('DB_NAME')) {
            return [
                'exists' => [],
                'missing' => [],
                'all_exist' => false,
                'error' => 'DB_NAME constant not defined'
            ];
        }
        
        $requiredTables = [
            'users', 'roles', 'capabilities', 'role_capabilities', 'user_roles',
            'post_types', 'posts', 'component_definitions', 'media', 'relationships',
            'taxonomies', 'terms', 'post_terms', 'post_type_taxonomies',
            'forms', 'form_submissions', 'actions', 'action_queue', 'settings',
            'post_revisions', 'activity_log', 'schema_migrations'
        ];
        
        try {
            $existingTables = [];
            
            // Method 1: Try SHOW TABLES with FETCH_COLUMN (most reliable)
            try {
                $stmt = $GLOBALS['db']->query("SHOW TABLES");
                $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $e1) {
                // Method 2: Try SHOW TABLES with FETCH_NUM and extract first column
                try {
                    $stmt = $GLOBALS['db']->query("SHOW TABLES");
                    $result = $stmt->fetchAll(PDO::FETCH_NUM);
                    if (!empty($result) && is_array($result[0])) {
                        $existingTables = array_column($result, 0);
                    }
                } catch (Exception $e2) {
                    // Method 3: Try information_schema
                    if (defined('DB_NAME')) {
                        $dbName = DB_NAME;
                        $stmt = $GLOBALS['db']->prepare("
                            SELECT TABLE_NAME 
                            FROM information_schema.TABLES 
                            WHERE TABLE_SCHEMA = ? 
                            AND TABLE_TYPE = 'BASE TABLE'
                        ");
                        $stmt->execute([$dbName]);
                        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    }
                }
            }
            
            // Normalize table names (remove database prefix if present, handle different formats)
            $existingTables = array_map(function($table) {
                if (is_array($table)) {
                    $table = $table[0] ?? '';
                }
                // Convert to string
                $table = (string)$table;
                // Remove database prefix if present (e.g., "modular.post_types" -> "post_types")
                if (defined('DB_NAME')) {
                    $table = str_replace(DB_NAME . '.', '', $table);
                }
                // Remove any other prefixes (handle backticks)
                $table = preg_replace('/^[^`]*`?[^`]*`?\./', '', $table);
                // Remove backticks
                $table = trim($table, '`');
                return $table;
            }, $existingTables);
            
            // Filter out empty values and re-index
            $existingTables = array_values(array_filter($existingTables, function($table) {
                return !empty($table);
            }));
            
            // Compare with required tables (case-insensitive comparison)
            $existingTablesLower = array_map('strtolower', $existingTables);
            $missingTables = [];
            foreach ($requiredTables as $required) {
                if (!in_array(strtolower($required), $existingTablesLower)) {
                    $missingTables[] = $required;
                }
            }
            
            return [
                'exists' => $existingTables,
                'missing' => $missingTables,
                'all_exist' => empty($missingTables),
                'error' => null
            ];
        } catch (Exception $e) {
            return [
                'exists' => [],
                'missing' => $requiredTables,
                'all_exist' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

if (!function_exists('run_pending_migrations')) {
    /**
     * Run any pending database migrations.
     * Safe to call on every request; only runs migrations that have not been executed.
     *
     * @return array ['ran' => string[], 'error' => string|null]
     */
    function run_pending_migrations() {
        if (!isset($GLOBALS['db'])) {
            return ['ran' => [], 'error' => 'Database connection not available'];
        }
        if (!defined('CORE_PATH')) {
            return ['ran' => [], 'error' => 'CORE_PATH not defined'];
        }
        $ran = [];
        try {
            require_once CORE_PATH . '/classes/Migration.php';
            $migration = new \Modular\Migration($GLOBALS['db']);
            $migrationsDir = CORE_PATH . '/migrations';
            $files = glob($migrationsDir . '/*_*.php');
            sort($files);
            foreach ($files as $file) {
                $basename = basename($file);
                if (!preg_match('/^(\d+)_(.+)\.php$/', $basename, $matches)) {
                    continue;
                }
                $version = $matches[1];
                $name = $matches[2];
                if ($migration->hasRun($version)) {
                    continue;
                }
                ob_start();
                try {
                    require $file;
                    $ran[] = $version . '_' . $name;
                } finally {
                    ob_end_clean();
                }
            }
            return ['ran' => $ran, 'error' => null];
        } catch (\Throwable $e) {
            return ['ran' => $ran, 'error' => $e->getMessage()];
        }
    }
}

if (!function_exists('get_database_connection')) {
    /**
     * Get a database connection
     * 
     * @return mysqli Database connection
     * @throws Exception if connection fails
     */
    function get_database_connection() {
        static $connection;
        
        if (!isset($connection)) {
            require_once ADMIN_PATH . '/config/config.php';
            
            $connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );
            
            if ($connection->connect_error) {
                throw new Exception('Database connection failed: ' . $connection->connect_error);
            }
            
            $connection->set_charset('utf8mb4');
        }
        
        return $connection;
    }
}
