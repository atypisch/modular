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
                    $migration->recordMigration($version, $name);
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

// ---------------------------------------------------------------------------
// md_db() — Modular DB accessor. Returns the PDO instance.
// Replace all $wpdb / global $wpdb usage with md_db()->...
// ---------------------------------------------------------------------------
if (!function_exists('md_db')) {
    function md_db(): PDO {
        if (!isset($GLOBALS['db']) || !($GLOBALS['db'] instanceof PDO)) {
            throw new \RuntimeException('Modular: database connection not initialised.');
        }
        return $GLOBALS['db'];
    }
}

if (!function_exists('md_db_query')) {
    /**
     * Execute a prepared SELECT and return all rows (associative arrays).
     * @param string $sql  SQL with ? placeholders
     * @param array  $params
     * @return array
     */
    function md_db_query(string $sql, array $params = []): array {
        try {
            $stmt = md_db()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('md_db_query error: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('md_db_row')) {
    /**
     * Execute a prepared SELECT and return the first row (associative array) or null.
     */
    function md_db_row(string $sql, array $params = []): ?array {
        try {
            $stmt = md_db()->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row : null;
        } catch (\PDOException $e) {
            error_log('md_db_row error: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('md_db_var')) {
    /**
     * Execute a prepared query and return the first column of the first row, or null.
     */
    function md_db_var(string $sql, array $params = []) {
        try {
            $stmt = md_db()->prepare($sql);
            $stmt->execute($params);
            $val = $stmt->fetchColumn();
            return $val !== false ? $val : null;
        } catch (\PDOException $e) {
            error_log('md_db_var error: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('md_db_insert')) {
    /**
     * Insert a row. Returns the new insert ID, or false on failure.
     * On failure, call md_db_last_error() for the PDO message (MySQL duplicate key, constraint, JSON, etc.).
     * @param string $table  Table name (not escaped — caller must use trusted strings)
     * @param array  $data   Associative [ column => value ]
     * @return int|false
     */
    function md_db_insert(string $table, array $data) {
        if (empty($data)) return false;
        $GLOBALS['md_db_last_sql_error'] = null;
        $cols   = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$table}` ({$cols}) VALUES ({$placeholders})";
        try {
            $stmt = md_db()->prepare($sql);
            $stmt->execute(array_values($data));
            return (int) md_db()->lastInsertId();
        } catch (\PDOException $e) {
            $GLOBALS['md_db_last_sql_error'] = $e->getMessage();
            error_log('md_db_insert error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('md_db_last_error')) {
    /** Last PDO error message from md_db_insert / md_db_update / md_db_exec, or null. */
    function md_db_last_error(): ?string {
        return isset($GLOBALS['md_db_last_sql_error']) ? (string) $GLOBALS['md_db_last_sql_error'] : null;
    }
}

if (!function_exists('md_db_update')) {
    /**
     * Update rows matching $where.
     * @param string $table
     * @param array  $data   [ column => value ] to SET
     * @param array  $where  [ column => value ] conditions (AND-joined)
     * @return int|false  Rows affected, or false on error.
     */
    function md_db_update(string $table, array $data, array $where) {
        if (empty($data) || empty($where)) return false;
        $GLOBALS['md_db_last_sql_error'] = null;
        $set  = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));
        $cond = implode(' AND ', array_map(fn($c) => "`{$c}` = ?", array_keys($where)));
        $sql  = "UPDATE `{$table}` SET {$set} WHERE {$cond}";
        try {
            $stmt = md_db()->prepare($sql);
            $stmt->execute([...array_values($data), ...array_values($where)]);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            $GLOBALS['md_db_last_sql_error'] = $e->getMessage();
            error_log('md_db_update error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('md_db_exec')) {
    /**
     * Execute a non-SELECT prepared statement (INSERT/UPDATE/DELETE with raw SQL).
     * Returns affected row count or false on error.
     */
    function md_db_exec(string $sql, array $params = []) {
        try {
            $stmt = md_db()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log('md_db_exec error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('md_db_delete')) {
    /**
     * Delete rows matching $where conditions (AND-joined).
     * Returns affected row count or false on error.
     */
    function md_db_delete(string $table, array $where) {
        if (empty($where)) return false;
        $cond = implode(' AND ', array_map(fn($c) => "`{$c}` = ?", array_keys($where)));
        $sql  = "DELETE FROM `{$table}` WHERE {$cond}";
        try {
            $stmt = md_db()->prepare($sql);
            $stmt->execute(array_values($where));
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log('md_db_delete error: ' . $e->getMessage());
            return false;
        }
    }

    function md_db_esc_like(string $text): string {
        return addcslashes($text, '_%\\');
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
