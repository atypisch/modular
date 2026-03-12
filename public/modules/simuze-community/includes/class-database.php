<?php
/**
 * Database management class
 * Handles all custom table creation and migrations
 */

if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
    exit;
}

class Simuze_Database {
    
    private static $instance = null;
    /** @var \Simuze_Wpdb_Compat|object wpdb-compatible handle */
    private $wpdb;
    private $prefix;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (defined('SIMUZE_MODULAR_RUN') && isset($GLOBALS['wpdb'])) {
            $this->wpdb = $GLOBALS['wpdb'];
            $this->prefix = SIMUZE_DB_PREFIX;
        } else {
            global $wpdb;
            $this->wpdb = $wpdb;
            $this->prefix = $wpdb->prefix . SIMUZE_DB_PREFIX;
        }
    }
    
    /**
     * Check and run migrations if needed
     */
    public function maybe_run_migrations() {
        if (defined('SIMUZE_MODULAR_RUN')) {
            // In Modular, schema is in full_schema_simuze.sql and admin/sql/schema.sql.
            // Ensure critical Simuze tables (like simuze_group_types) exist so
            // deactivating/activating the module repairs missing tables.
            if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
                try {
                    $pdo = $GLOBALS['db'];
                    $stmt = $pdo->query("SHOW TABLES LIKE 'simuze_group_types'");
                    $exists = $stmt ? $stmt->fetchColumn() : false;
                    if ($exists !== 'simuze_group_types') {
                        $pdo->exec("
CREATE TABLE IF NOT EXISTS simuze_group_types (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY slug (slug),
    KEY deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
                        $pdo->exec("
INSERT IGNORE INTO simuze_group_types (name, slug, icon) VALUES
('Artist', 'artist', 'music'),
('Label', 'label', 'tag'),
('Venue', 'venue', 'map-marker'),
('City', 'city', 'city'),
('Province', 'province', 'map'),
('Country', 'country', 'globe');
");
                    }
                } catch (\Throwable $e) {
                    // Fail silently in Modular context; schema can still be applied via SQL.
                }
            }
            $current = get_option('simuze_community_db_version', '0.0.0');
            if (version_compare($current, SIMUZE_COMMUNITY_VERSION, '<')) {
                update_option('simuze_community_db_version', SIMUZE_COMMUNITY_VERSION);
            }
            return;
        }
        $current_version = get_option('simuze_community_db_version', '0.0.0');
        $plugin_version = SIMUZE_COMMUNITY_VERSION;
        
        // Always ensure tables exist (idempotent)
        $needs_table_creation = $this->ensure_tables_exist();
        
        // Also check if unique_handle column exists (for manual migration trigger)
        $table_name = $this->prefix . 'groups';
        $columns = $this->wpdb->get_col("DESCRIBE $table_name");
        $needs_migration = !in_array('unique_handle', $columns);
        
        // Check if aliases table exists
        $aliases_table = $this->prefix . 'group_handle_aliases';
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '$aliases_table'");
        $needs_migration = $needs_migration || !$table_exists;
        
        // Check if username migration is needed (users with invalid usernames)
        $users_table = $this->prefix . 'users';
        $invalid_usernames = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM $users_table WHERE deleted_at IS NULL AND (username LIKE '%@%' OR username REGEXP '[^a-z0-9-]' OR username REGEXP '[A-Z]')"
        );
        $needs_migration = $needs_migration || ($invalid_usernames > 0);
        
        if ($needs_table_creation || version_compare($current_version, $plugin_version, '<') || $needs_migration) {
            $this->run_migrations();
            update_option('simuze_community_db_version', $plugin_version);
        }
    }

    /**
     * Ensure all required tables exist; returns true if creation is needed.
     */
    private function ensure_tables_exist() {
        $required = array(
            'users','profiles','groups','group_types','group_members','group_invitations',
            'group_activity','user_activity','messages','notifications','reviews',
            'group_handle_aliases','reports',
            // 'discography','discography_items', // REMOVED: No longer used, using wp_music_albums and wp_music_uploads instead
            'follows','friends',
            // 'songs','albums','album_songs', // REMOVED: No longer used, using wp_music_uploads and wp_music_albums instead
            'discounts','orders',
            'order_items','download_tokens','seller_accounts','transactions','payouts',
            'avatar_cache_user','avatar_cache_group','group_payments',
            'label_artists', // Label-Artist relationships
            'webhooks', // Webhook storage for external integrations
            // Subscription system tables
            'subscriptions','plays_total','plays_subscription','artist_payouts'
        );
        
        $missing = array();
        foreach ($required as $name) {
            $table = $this->prefix . $name;
            $exists = $this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($exists !== $table) {
                $missing[] = $table;
            }
        }
        
        if (!empty($missing)) {
            $this->create_tables();
            return true;
        }
        
        return false;
    }
    
    /**
     * Run database migrations
     */
    private function run_migrations() {
        // Add deleted_at to group_types if it doesn't exist
        $table_name = $this->prefix . 'group_types';
        $columns = $this->wpdb->get_col("DESCRIBE $table_name");
        
        if (!in_array('deleted_at', $columns)) {
            $this->wpdb->query("ALTER TABLE $table_name ADD COLUMN deleted_at datetime DEFAULT NULL");
            $this->wpdb->query("ALTER TABLE $table_name ADD KEY deleted_at (deleted_at)");
        }
        
        // Ensure groups table has lat/lng columns (for older installs created before location fields)
        $groups_table = $this->prefix . 'groups';
        $groups_columns = $this->wpdb->get_col("DESCRIBE $groups_table");
        if (!in_array('lat', $groups_columns)) {
            $this->wpdb->query("ALTER TABLE $groups_table ADD COLUMN lat decimal(10,8) DEFAULT NULL AFTER geo_id");
        }
        if (!in_array('lng', $groups_columns)) {
            $this->wpdb->query("ALTER TABLE $groups_table ADD COLUMN lng decimal(11,8) DEFAULT NULL AFTER lat");
        }
        
        // Re-run location backfills so existing country/province/city groups get lat/lng
        delete_option('simuze_province_geo_backfilled');
        delete_option('simuze_country_geo_backfilled');
        delete_option('simuze_city_geo_backfilled');
        
        // Migrate users with email addresses as usernames
        $this->migrate_user_email_usernames();
        
        // Run create_tables to ensure all tables are up to date
        // NOTE: Removed old tables (songs, albums, album_songs, discography, discography_items) 
        // are no longer created - using wp_music_uploads and wp_music_albums instead
        $this->create_tables();
    }
    
    /**
     * Migrate users with email addresses or invalid characters in usernames to clean usernames
     */
    private function migrate_user_email_usernames() {
        $users_table = $this->prefix . 'users';
        
        // Find users with email addresses, uppercase letters, or invalid characters in usernames
        // Username should only contain: lowercase letters, numbers, and hyphens
        $users = $this->wpdb->get_results(
            "SELECT id, username, email FROM $users_table WHERE deleted_at IS NULL"
        );
        
        foreach ($users as $user) {
            $needs_migration = false;
            
            // Check if username contains invalid characters
            if (preg_match('/[^a-z0-9-]/', $user->username) || // Contains invalid chars
                strpos($user->username, '@') !== false || // Contains @
                preg_match('/[A-Z]/', $user->username)) { // Contains uppercase
                $needs_migration = true;
            }
            
            if ($needs_migration) {
                // Generate clean username using the same logic as Simuze_User::generate_unique_username
                $new_username = Simuze_User::generate_unique_username($user->username);
                
                // Only update if different
                if ($new_username !== $user->username) {
                    $this->wpdb->update(
                        $users_table,
                        array('username' => $new_username),
                        array('id' => $user->id),
                        array('%s'),
                        array('%d')
                    );
                }
            }
        }
    }
    
    /**
     * Create all custom tables
     */
    public function create_tables() {
        if (defined('SIMUZE_MODULAR_RUN')) {
            return; // Schema in full_schema_simuze.sql
        }
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Users table
        $this->create_users_table($charset_collate);
        
        // Profiles table
        $this->create_profiles_table($charset_collate);
        
        // Groups table
        $this->create_groups_table($charset_collate);
        
        // Group types table
        $this->create_group_types_table($charset_collate);
        
        // Group members table
        $this->create_group_members_table($charset_collate);
        
        // Group invitations table
        $this->create_group_invitations_table($charset_collate);
        
        // Group activity table
        $this->create_group_activity_table($charset_collate);
        
        // User activity table
        $this->create_user_activity_table($charset_collate);
        
        // Messages table
        $this->create_messages_table($charset_collate);
        
        // Notifications table
        $this->create_notifications_table($charset_collate);
        
        // Reviews table
        $this->create_reviews_table($charset_collate);
        
        // Group handle aliases table
        $this->create_group_handle_aliases_table($charset_collate);
        
        // Reports table
        $this->create_reports_table($charset_collate);
        
        // Discography table (existing structure) - REMOVED: No longer used, using wp_music_albums instead
        // $this->create_discography_table($charset_collate);
        
        // Discography items table - REMOVED: No longer used, using wp_music_uploads instead
        // $this->create_discography_items_table($charset_collate);
        
        // Follows table
        $this->create_follows_table($charset_collate);
        
        // Friends table
        $this->create_friends_table($charset_collate);
        
        // Commerce tables - REMOVED: No longer used, using wp_music_uploads and wp_music_albums instead
        // $this->create_songs_table($charset_collate);
        // $this->create_albums_table($charset_collate);
        // $this->create_album_songs_table($charset_collate);
        $this->create_discounts_table($charset_collate);
        $this->create_orders_table($charset_collate);
        $this->create_order_items_table($charset_collate);
        $this->create_download_tokens_table($charset_collate);
        $this->create_seller_accounts_table($charset_collate);
        $this->create_transactions_table($charset_collate);
        $this->create_payouts_table($charset_collate);

        // Avatar cache tables (split user/group)
        $this->create_avatar_cache_user_table($charset_collate);
        $this->create_avatar_cache_group_table($charset_collate);
        
        // Group payments table
        $this->create_group_payments_table($charset_collate);
        
        // Label-Artist relationships table
        $this->create_label_artists_table($charset_collate);
        
        // Webhooks table
        $this->create_webhooks_table($charset_collate);
        
        // Subscription system tables
        $this->create_subscriptions_table($charset_collate);
        $this->create_plays_total_table($charset_collate);
        $this->create_plays_subscription_table($charset_collate);
        $this->create_artist_payouts_table($charset_collate);
    }
    
    /**
     * Create users table
     */
    private function create_users_table($charset_collate) {
        $table_name = $this->prefix . 'users';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            simezu_id varchar(255) NOT NULL,
            username varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            display_name varchar(255) DEFAULT NULL,
            avatar_url varchar(500) DEFAULT NULL,
            header_url varchar(500) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY simezu_id (simezu_id),
            UNIQUE KEY username (username),
            UNIQUE KEY email (email),
            KEY status (status),
            KEY deleted_at (deleted_at)
        ) $charset_collate;";
        
        dbDelta($sql);

        // Backfill header_url column if missing on existing installs
        $columns = $this->wpdb->get_col("DESCRIBE $table_name");
        if (!in_array('header_url', $columns)) {
            $this->wpdb->query("ALTER TABLE $table_name ADD COLUMN header_url varchar(500) DEFAULT NULL AFTER avatar_url");
        }
    }
    
    /**
     * Create profiles table
     */
    private function create_profiles_table($charset_collate) {
        $table_name = $this->prefix . 'profiles';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            bio text DEFAULT NULL,
            location varchar(255) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            province varchar(100) DEFAULT NULL,
            country varchar(100) DEFAULT NULL,
            geo_id varchar(50) DEFAULT NULL,
            lat decimal(10,8) DEFAULT NULL,
            lng decimal(11,8) DEFAULT NULL,
            website varchar(255) DEFAULT NULL,
            social_links longtext DEFAULT NULL,
            settings longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY location (city, province, country)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create groups table
     */
    private function create_groups_table($charset_collate) {
        $table_name = $this->prefix . 'groups';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            unique_name varchar(255) DEFAULT NULL,
            description text DEFAULT NULL,
            group_type_id bigint(20) UNSIGNED DEFAULT NULL,
            creator_id bigint(20) UNSIGNED NOT NULL,
            avatar_url varchar(500) DEFAULT NULL,
            header_url varchar(500) DEFAULT NULL,
            status varchar(20) DEFAULT 'public',
            city varchar(100) DEFAULT NULL,
            province varchar(100) DEFAULT NULL,
            country varchar(100) DEFAULT NULL,
            geo_id varchar(50) DEFAULT NULL,
            lat decimal(10,8) DEFAULT NULL,
            lng decimal(11,8) DEFAULT NULL,
            settings longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY unique_name (unique_name),
            KEY group_type_id (group_type_id),
            KEY creator_id (creator_id),
            KEY status (status),
            KEY location (city, province, country),
            KEY deleted_at (deleted_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Add unique_name column if it doesn't exist (for existing installations)
        $columns = $this->wpdb->get_col("DESCRIBE $table_name");
        if (!in_array('unique_name', $columns)) {
            $this->wpdb->query("ALTER TABLE $table_name ADD COLUMN unique_name varchar(255) DEFAULT NULL AFTER slug");
            $this->wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY unique_name (unique_name)");
            
            // Generate unique_name for existing groups based on slug
            $this->migrate_groups_unique_names();
        }
        
        // Migrate to unique_handle system
        $this->migrate_groups_to_unique_handle();
    }
    
    /**
     * Migrate existing groups to have unique_name
     */
    private function migrate_groups_unique_names() {
        $table_name = $this->prefix . 'groups';
        $groups = $this->wpdb->get_results("SELECT id, slug, name FROM $table_name WHERE unique_name IS NULL AND deleted_at IS NULL");
        
        foreach ($groups as $group) {
            $unique_name = sanitize_title($group->slug);
            
            // Ensure uniqueness
            $counter = 1;
            $original_unique_name = $unique_name;
            while ($this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM $table_name WHERE unique_name = %s AND id != %d",
                $unique_name,
                $group->id
            ))) {
                $unique_name = $original_unique_name . '-' . $counter;
                $counter++;
            }
            
            $this->wpdb->update(
                $table_name,
                array('unique_name' => $unique_name),
                array('id' => $group->id),
                array('%s'),
                array('%d')
            );
        }
    }
    
    /**
     * Create group types table
     */
    private function create_group_types_table($charset_collate) {
        $table_name = $this->prefix . 'group_types';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            icon varchar(100) DEFAULT NULL,
            description text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY deleted_at (deleted_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Add deleted_at column if it doesn't exist (for existing installations)
        $columns = $this->wpdb->get_col("DESCRIBE $table_name");
        if (!in_array('deleted_at', $columns)) {
            $this->wpdb->query("ALTER TABLE $table_name ADD COLUMN deleted_at datetime DEFAULT NULL");
            $this->wpdb->query("ALTER TABLE $table_name ADD KEY deleted_at (deleted_at)");
        }
        
        // Insert default group types
        $this->insert_default_group_types();
    }
    
    /**
     * Insert default group types
     */
    private function insert_default_group_types() {
        $default_types = array(
            array('name' => 'Artist', 'slug' => 'artist', 'icon' => 'music'),
            array('name' => 'Label', 'slug' => 'label', 'icon' => 'tag'),
            array('name' => 'Venue', 'slug' => 'venue', 'icon' => 'map-marker'),
            array('name' => 'City', 'slug' => 'city', 'icon' => 'city'),
            array('name' => 'Province', 'slug' => 'province', 'icon' => 'map'),
            array('name' => 'Country', 'slug' => 'country', 'icon' => 'globe'),
        );
        
        foreach ($default_types as $type) {
            $exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->prefix}group_types WHERE slug = %s",
                $type['slug']
            ));
            
            if (!$exists) {
                $this->wpdb->insert(
                    $this->prefix . 'group_types',
                    $type,
                    array('%s', '%s', '%s')
                );
            }
        }
    }
    
    /**
     * Create group members table
     */
    private function create_group_members_table($charset_collate) {
        $table_name = $this->prefix . 'group_members';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            role varchar(20) DEFAULT 'member',
            status varchar(20) DEFAULT 'active',
            invited_by bigint(20) UNSIGNED DEFAULT NULL,
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY group_user (group_id, user_id),
            KEY user_id (user_id),
            KEY role (role),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create group invitations table
     */
    private function create_group_invitations_table($charset_collate) {
        $table_name = $this->prefix . 'group_invitations';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            invited_by bigint(20) UNSIGNED NOT NULL,
            message text DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY group_id (group_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create group activity table
     */
    private function create_group_activity_table($charset_collate) {
        $table_name = $this->prefix . 'group_activity';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            action varchar(50) NOT NULL,
            content text DEFAULT NULL,
            component varchar(50) DEFAULT NULL,
            item_id bigint(20) UNSIGNED DEFAULT NULL,
            meta longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY group_id (group_id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create user activity table
     */
    private function create_user_activity_table($charset_collate) {
        $table_name = $this->prefix . 'user_activity';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            action varchar(50) NOT NULL,
            content text DEFAULT NULL,
            component varchar(50) DEFAULT NULL,
            item_id bigint(20) UNSIGNED DEFAULT NULL,
            meta longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create messages table
     */
    private function create_messages_table($charset_collate) {
        $table_name = $this->prefix . 'messages';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            thread_id bigint(20) UNSIGNED NOT NULL,
            sender_id bigint(20) UNSIGNED NOT NULL,
            recipient_id bigint(20) UNSIGNED DEFAULT NULL,
            group_id bigint(20) UNSIGNED DEFAULT NULL,
            subject varchar(255) DEFAULT NULL,
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY thread_id (thread_id),
            KEY sender_id (sender_id),
            KEY recipient_id (recipient_id),
            KEY group_id (group_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create notifications table
     */
    private function create_notifications_table($charset_collate) {
        $table_name = $this->prefix . 'notifications';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            link varchar(500) DEFAULT NULL,
            meta longtext DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            read_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create reviews table
     */
    private function create_reviews_table($charset_collate) {
        $table_name = $this->prefix . 'reviews';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reviewer_id bigint(20) UNSIGNED NOT NULL,
            content_type varchar(50) NOT NULL,
            content_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) DEFAULT 'pending',
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY reviewer_id (reviewer_id),
            KEY content (content_type, content_id),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create reports table
     */
    private function create_reports_table($charset_collate) {
        $table_name = $this->prefix . 'reports';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reporter_id bigint(20) UNSIGNED NOT NULL,
            content_type varchar(50) NOT NULL,
            content_id bigint(20) UNSIGNED NOT NULL,
            report_type varchar(50) NOT NULL,
            comment text DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            reviewed_by bigint(20) UNSIGNED DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY reporter_id (reporter_id),
            KEY content (content_type, content_id),
            KEY status (status),
            KEY reviewed_by (reviewed_by)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create discography table (existing structure)
     * DISABLED: No longer used, using wp_music_albums instead
     */
    private function create_discography_table($charset_collate) {
        return; // DISABLED - table no longer used
        $table_name = $this->prefix . 'discography';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id bigint(20) UNSIGNED NOT NULL,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text DEFAULT NULL,
            cover_url varchar(500) DEFAULT NULL,
            release_date date DEFAULT NULL,
            status varchar(20) DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY group_id (group_id),
            KEY status (status),
            KEY deleted_at (deleted_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create discography items table
     * DISABLED: No longer used, using wp_music_uploads instead
     */
    private function create_discography_items_table($charset_collate) {
        return; // DISABLED - table no longer used
        $table_name = $this->prefix . 'discography_items';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            discography_id bigint(20) UNSIGNED NOT NULL,
            title varchar(255) NOT NULL,
            track_number int(11) DEFAULT NULL,
            file_url varchar(500) DEFAULT NULL,
            duration int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY discography_id (discography_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create follows table
     */
    private function create_follows_table($charset_collate) {
        $table_name = $this->prefix . 'follows';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            follower_id bigint(20) UNSIGNED NOT NULL,
            followable_type varchar(50) NOT NULL,
            followable_id bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY follow (follower_id, followable_type, followable_id),
            KEY follower_id (follower_id),
            KEY followable (followable_type, followable_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create friends table
     */
    private function create_friends_table($charset_collate) {
        $table_name = $this->prefix . 'friends';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            friend_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) DEFAULT 'pending',
            requested_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY friendship (user_id, friend_id),
            KEY user_id (user_id),
            KEY friend_id (friend_id),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create songs table (products)
     * DISABLED: No longer used, using wp_music_uploads instead
     */
    private function create_songs_table($charset_collate) {
        return; // DISABLED - table no longer used
        $table_name = $this->prefix . 'songs';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id bigint(20) UNSIGNED NOT NULL,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text DEFAULT NULL,
            price decimal(10,2) DEFAULT NULL,
            download_price_min decimal(10,2) DEFAULT NULL,
            download_price_default decimal(10,2) DEFAULT NULL,
            file_path varchar(500) NOT NULL,
            file_size bigint(20) DEFAULT NULL,
            duration int(11) DEFAULT NULL,
            artwork_url varchar(500) DEFAULT NULL,
            genre varchar(100) DEFAULT NULL,
            license varchar(50) DEFAULT 'cc-by',
            is_active tinyint(1) DEFAULT 1,
            is_purchasable tinyint(1) DEFAULT 1,
            download_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY group_id (group_id),
            KEY is_active (is_active),
            KEY is_purchasable (is_purchasable),
            KEY deleted_at (deleted_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create albums table
     * DISABLED: No longer used, using wp_music_albums instead
     */
    private function create_albums_table($charset_collate) {
        return; // DISABLED - table no longer used
        $table_name = $this->prefix . 'albums';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id bigint(20) UNSIGNED NOT NULL,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text DEFAULT NULL,
            price decimal(10,2) DEFAULT NULL,
            download_price_min decimal(10,2) DEFAULT NULL,
            download_price_default decimal(10,2) DEFAULT NULL,
            cover_image varchar(500) DEFAULT NULL,
            release_date date DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_purchasable tinyint(1) DEFAULT 1,
            download_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY group_id (group_id),
            KEY is_active (is_active),
            KEY is_purchasable (is_purchasable),
            KEY release_date (release_date),
            KEY deleted_at (deleted_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create album_songs junction table
     * DISABLED: No longer used, using wp_music_uploads with album_id instead
     */
    private function create_album_songs_table($charset_collate) {
        return; // DISABLED - table no longer used
        $table_name = $this->prefix . 'album_songs';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            album_id bigint(20) UNSIGNED NOT NULL,
            song_id bigint(20) UNSIGNED NOT NULL,
            track_number int(11) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY album_song (album_id, song_id),
            KEY album_id (album_id),
            KEY song_id (song_id),
            KEY track_number (track_number)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create discounts table
     */
    private function create_discounts_table($charset_collate) {
        $table_name = $this->prefix . 'discounts';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            code varchar(50) DEFAULT NULL,
            target_type enum('global','group','product') NOT NULL DEFAULT 'global',
            target_id bigint(20) UNSIGNED DEFAULT NULL,
            product_type enum('song','album','all') DEFAULT 'all',
            discount_type enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
            discount_value decimal(10,2) NOT NULL,
            min_purchase decimal(10,2) DEFAULT NULL,
            max_uses int(11) DEFAULT NULL,
            uses_count int(11) DEFAULT 0,
            active_from datetime DEFAULT NULL,
            active_until datetime DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY target (target_type, target_id),
            KEY is_active (is_active),
            KEY active_period (active_from, active_until)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create orders table
     */
    private function create_orders_table($charset_collate) {
        $table_name = $this->prefix . 'orders';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            buyer_id bigint(20) UNSIGNED NOT NULL,
            buyer_email varchar(255) NOT NULL,
            subtotal decimal(10,2) NOT NULL DEFAULT 0,
            discount_amount decimal(10,2) DEFAULT 0,
            total_price decimal(10,2) NOT NULL DEFAULT 0,
            currency varchar(3) DEFAULT 'EUR',
            payment_status enum('pending','processing','paid','failed','refunded','cancelled') DEFAULT 'pending',
            payment_method varchar(50) DEFAULT NULL,
            payment_session_id varchar(255) DEFAULT NULL,
            simezu_transaction_id varchar(255) DEFAULT NULL,
            discount_code varchar(50) DEFAULT NULL,
            notes text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            paid_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY order_number (order_number),
            KEY buyer_id (buyer_id),
            KEY payment_status (payment_status),
            KEY payment_session_id (payment_session_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create order_items table
     */
    private function create_order_items_table($charset_collate) {
        $table_name = $this->prefix . 'order_items';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            seller_type enum('user','group') NOT NULL,
            seller_id bigint(20) UNSIGNED NOT NULL,
            product_type enum('song','album') NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            product_title varchar(255) NOT NULL,
            quantity int(11) DEFAULT 1,
            unit_price decimal(10,2) NOT NULL,
            discount_amount decimal(10,2) DEFAULT 0,
            total_price decimal(10,2) NOT NULL,
            simuze_fee decimal(10,2) DEFAULT 0,
            seller_earnings decimal(10,2) DEFAULT 0,
            simezu_transaction_id varchar(255) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY seller (seller_type, seller_id),
            KEY product (product_type, product_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create download_tokens table
     */
    private function create_download_tokens_table($charset_collate) {
        $table_name = $this->prefix . 'download_tokens';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            token varchar(64) NOT NULL,
            order_item_id bigint(20) UNSIGNED NOT NULL,
            buyer_id bigint(20) UNSIGNED NOT NULL,
            product_type enum('song','album') NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            download_count int(11) DEFAULT 0,
            max_downloads int(11) DEFAULT 5,
            expires_at datetime DEFAULT NULL,
            last_download_at datetime DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY order_item_id (order_item_id),
            KEY buyer_id (buyer_id),
            KEY product (product_type, product_id),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create seller_accounts table (payment account linking)
     */
    private function create_seller_accounts_table($charset_collate) {
        $table_name = $this->prefix . 'seller_accounts';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            seller_type enum('user','group') NOT NULL,
            seller_id bigint(20) UNSIGNED NOT NULL,
            simezu_account_id varchar(255) DEFAULT NULL,
            payment_provider enum('paypal','stripe') DEFAULT 'paypal',
            provider_account_id varchar(255) DEFAULT NULL,
            provider_email varchar(255) DEFAULT NULL,
            is_verified tinyint(1) DEFAULT 0,
            payout_threshold decimal(10,2) DEFAULT 25.00,
            total_earnings decimal(10,2) DEFAULT 0,
            pending_payout decimal(10,2) DEFAULT 0,
            total_paid_out decimal(10,2) DEFAULT 0,
            last_payout_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY seller (seller_type, seller_id),
            KEY simezu_account_id (simezu_account_id),
            KEY payment_provider (payment_provider),
            KEY is_verified (is_verified)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create payouts table
     */
    private function create_payouts_table($charset_collate) {
        $table_name = $this->prefix . 'payouts';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            seller_type enum('user','group') NOT NULL,
            seller_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(10,2) NOT NULL,
            status enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
            payment_provider enum('paypal','stripe') DEFAULT 'paypal',
            provider_account_id varchar(255) DEFAULT NULL,
            transaction_id varchar(255) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            cancelled_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY seller (seller_type, seller_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create transactions table (local cache of SIMEZU transactions)
     */
    private function create_transactions_table($charset_collate) {
        $table_name = $this->prefix . 'transactions';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            simezu_transaction_id varchar(255) NOT NULL,
            order_id bigint(20) UNSIGNED NOT NULL,
            order_item_id bigint(20) UNSIGNED DEFAULT NULL,
            seller_type enum('user','group') NOT NULL,
            seller_id bigint(20) UNSIGNED NOT NULL,
            transaction_type enum('sale','refund','payout','fee') NOT NULL DEFAULT 'sale',
            gross_amount decimal(10,2) NOT NULL,
            simuze_fee decimal(10,2) DEFAULT 0,
            payment_provider_fee decimal(10,2) DEFAULT 0,
            net_amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'EUR',
            status enum('pending','completed','failed','refunded') DEFAULT 'pending',
            payout_status enum('pending','scheduled','processing','paid','failed') DEFAULT 'pending',
            payout_batch_id varchar(255) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY simezu_transaction_id (simezu_transaction_id),
            KEY order_id (order_id),
            KEY order_item_id (order_item_id),
            KEY seller (seller_type, seller_id),
            KEY transaction_type (transaction_type),
            KEY status (status),
            KEY payout_status (payout_status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create group handle aliases table
     */
    private function create_group_handle_aliases_table($charset_collate) {
        $table_name = $this->prefix . 'group_handle_aliases';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id bigint(20) UNSIGNED NOT NULL,
            old_handle varchar(64) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY old_handle (old_handle),
            KEY group_id (group_id),
            FOREIGN KEY (group_id) REFERENCES {$this->prefix}groups(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Migrate unique_name to unique_handle and add unique_handle column
     */
    private function migrate_groups_to_unique_handle() {
        $table_name = $this->prefix . 'groups';
        $columns = $this->wpdb->get_col("DESCRIBE $table_name");
        
        // Add unique_handle column if it doesn't exist
        if (!in_array('unique_handle', $columns)) {
            $this->wpdb->query("ALTER TABLE $table_name ADD COLUMN unique_handle varchar(64) DEFAULT NULL AFTER unique_name");
            $this->wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY unique_handle (unique_handle)");
            
            // Migrate existing unique_name values to unique_handle
            $groups = $this->wpdb->get_results("SELECT id, unique_name, name FROM $table_name WHERE unique_name IS NOT NULL AND (unique_handle IS NULL OR unique_handle = '')");
            
            foreach ($groups as $group) {
                $handle = $this->slugify_handle($group->unique_name ?: $group->name);
                $counter = 1;
                $original_handle = $handle;
                
                // Ensure uniqueness
                while ($this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT id FROM $table_name WHERE unique_handle = %s AND id != %d",
                    $handle,
                    $group->id
                ))) {
                    $handle = $original_handle . '-' . $counter;
                    $counter++;
                }
                
                $this->wpdb->update(
                    $table_name,
                    array('unique_handle' => $handle),
                    array('id' => $group->id),
                    array('%s'),
                    array('%d')
                );
            }
        }
    }
    
    /**
     * Slugify helper for handles
     */
    private function slugify_handle($text) {
        // Convert to lowercase
        $text = strtolower($text);
        // Replace spaces and underscores with hyphens
        $text = preg_replace('/[\s_]+/', '-', $text);
        // Remove all non-alphanumeric characters except hyphens
        $text = preg_replace('/[^a-z0-9-]/', '', $text);
        // Remove multiple consecutive hyphens
        $text = preg_replace('/-+/', '-', $text);
        // Trim hyphens from start and end
        $text = trim($text, '-');
        return $text;
    }
    
    /**
     * Create avatar cache table
     * Stores cached avatar metadata from SIMEZU API
     */
    private function create_avatar_cache_user_table($charset_collate) {
        $table_name = $this->prefix . 'avatar_cache_user';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id varchar(255) NOT NULL COMMENT 'SIMEZU user id',
            thumb_url varchar(500) DEFAULT NULL,
            medium_url varchar(500) DEFAULT NULL,
            full_url varchar(500) DEFAULT NULL,
            metadata longtext DEFAULT NULL COMMENT 'JSON metadata from Simezu',
            cached_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL COMMENT 'Cache expires 24h after cached_at',
            PRIMARY KEY (id),
            UNIQUE KEY user_lookup (user_id),
            KEY expires_at (expires_at),
            KEY cached_at (cached_at)
        ) $charset_collate;";

        dbDelta($sql);
    }

    private function create_avatar_cache_group_table($charset_collate) {
        $table_name = $this->prefix . 'avatar_cache_group';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id bigint(20) UNSIGNED NOT NULL,
            thumb_url varchar(500) DEFAULT NULL,
            medium_url varchar(500) DEFAULT NULL,
            full_url varchar(500) DEFAULT NULL,
            metadata longtext DEFAULT NULL COMMENT 'JSON metadata for local storage',
            cached_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY group_lookup (group_id),
            KEY cached_at (cached_at)
        ) $charset_collate;";

        dbDelta($sql);
    }
    
    /**
     * Create group_payments table
     */
    private function create_group_payments_table($charset_collate) {
        $table_name = $this->prefix . 'group_payments';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id bigint(20) UNSIGNED NOT NULL,
            member_id bigint(20) UNSIGNED DEFAULT NULL,
            paypal_email varchar(255) DEFAULT NULL,
            stripe_connect_id varchar(255) DEFAULT NULL,
            description varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY group_id (group_id),
            KEY member_id (member_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Add unique constraint for non-null member_id only (handled at application level)
        // MySQL allows multiple NULL values in UNIQUE constraints, but we'll enforce uniqueness in code
    }
    
    /**
     * Create label_artists table (Label-Artist relationships)
     */
    private function create_label_artists_table($charset_collate) {
        $table_name = $this->prefix . 'label_artists';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            label_group_id bigint(20) UNSIGNED NOT NULL,
            artist_group_id bigint(20) UNSIGNED NOT NULL,
            status enum('pending','accepted','rejected','revoked') DEFAULT 'pending',
            message text DEFAULT NULL,
            invited_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY label_artist (label_group_id, artist_group_id),
            KEY label_group_id (label_group_id),
            KEY artist_group_id (artist_group_id),
            KEY status (status),
            KEY invited_by (invited_by)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create webhooks table
     */
    private function create_webhooks_table($charset_collate) {
        $table_name = $this->prefix . 'webhooks';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            provider varchar(100) NOT NULL,
            event_type varchar(100) NOT NULL,
            external_reference varchar(255) NOT NULL,
            payload longtext NOT NULL,
            status varchar(20) DEFAULT 'received',
            error_message text DEFAULT NULL,
            processed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY external_reference (external_reference),
            KEY provider (provider),
            KEY event_type (event_type),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create subscriptions table
     */
    private function create_subscriptions_table($charset_collate) {
        $table_name = $this->prefix . 'subscriptions';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'free',
            price int(11) NOT NULL DEFAULT 0,
            currency varchar(3) NOT NULL DEFAULT 'EUR',
            simezu_subscription_id varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            started_at datetime NOT NULL,
            expires_at datetime DEFAULT NULL,
            cancelled_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY status (status),
            KEY simezu_subscription_id (simezu_subscription_id),
            KEY started_at (started_at),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create plays_total table - aggregated play counts per track/artist/period
     * Optimized for high-volume writes with atomic increments
     */
    private function create_plays_total_table($charset_collate) {
        $table_name = $this->prefix . 'plays_total';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            track_id bigint(20) UNSIGNED NOT NULL,
            artist_id bigint(20) UNSIGNED NOT NULL,
            period char(7) NOT NULL,
            play_count bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY track_artist_period (track_id, artist_id, period),
            KEY artist_id (artist_id),
            KEY period (period),
            KEY artist_period (artist_id, period),
            KEY play_count (play_count)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create plays_subscription table - subscription-attributed plays
     * Used for revenue distribution calculation
     */
    private function create_plays_subscription_table($charset_collate) {
        $table_name = $this->prefix . 'plays_subscription';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) UNSIGNED NOT NULL,
            track_id bigint(20) UNSIGNED NOT NULL,
            artist_id bigint(20) UNSIGNED NOT NULL,
            period char(7) NOT NULL,
            play_count bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY sub_track_artist_period (subscription_id, track_id, artist_id, period),
            KEY subscription_id (subscription_id),
            KEY artist_id (artist_id),
            KEY period (period),
            KEY artist_period (artist_id, period)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create artist_payouts table - tracks payout calculations and status
     */
    private function create_artist_payouts_table($charset_collate) {
        $table_name = $this->prefix . 'artist_payouts';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            artist_id bigint(20) UNSIGNED NOT NULL,
            period char(7) NOT NULL,
            play_count bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            play_share decimal(10,6) NOT NULL DEFAULT 0,
            amount int(11) NOT NULL DEFAULT 0,
            currency varchar(3) NOT NULL DEFAULT 'EUR',
            batch_id varchar(100) DEFAULT NULL,
            simezu_payout_id varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            error_message text DEFAULT NULL,
            paid_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY artist_period (artist_id, period),
            KEY artist_id (artist_id),
            KEY period (period),
            KEY batch_id (batch_id),
            KEY status (status),
            KEY simezu_payout_id (simezu_payout_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Get table name with prefix
     */
    public function get_table_name($table) {
        return $this->prefix . $table;
    }
}

