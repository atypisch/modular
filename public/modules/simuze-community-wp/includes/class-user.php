<?php
/**
 * User management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_User {
    
    public $id;
    public $simezu_id;
    public $username;
    public $email;
    public $display_name;
    public $avatar_url;
    public $header_url;
    public $status;
    public $created_at;
    public $updated_at;
    public $profile;
    
    private $db;
    private $table;
    
    /**
     * Constructor
     */
    public function __construct($data = null) {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        if ($data) {
            $this->populate($data);
        }
    }
    
    /**
     * Populate object from data
     */
    private function populate($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        // Load profile
        if ($this->id) {
            $this->load_profile();
        }
    }
    
    /**
     * Load user profile
     */
    private function load_profile() {
        $profile_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'profiles';
        $profile = $this->db->get_row($this->db->prepare(
            "SELECT * FROM $profile_table WHERE user_id = %d",
            $this->id
        ), ARRAY_A);
        
        if ($profile) {
            // Parse websites from social_links JSON if available
            if (!empty($profile['social_links'])) {
                $social_links = json_decode($profile['social_links'], true);
                if (is_array($social_links) && isset($social_links['websites'])) {
                    $profile['websites'] = $social_links['websites'];
                }
            }
            // Ensure websites is always an array
            if (!isset($profile['websites']) || !is_array($profile['websites'])) {
                $profile['websites'] = array();
            }
            $this->profile = $profile;
        } else {
            $this->profile = null;
        }
    }
    
    /**
     * Get user by ID
     */
    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND deleted_at IS NULL",
            $id
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Get user by Simezu ID
     */
    public static function get_by_simezu_id($simezu_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE simezu_id = %s AND deleted_at IS NULL",
            $simezu_id
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Get user by email
     */
    public static function get_by_email($email) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE email = %s AND deleted_at IS NULL",
            $email
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Get user by username or email
     * Supports both username and email lookup for flexibility
     * Also handles old email-based usernames by redirecting to clean username
     */
    public static function get_by_username($username) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        // If username contains invalid characters (like @), try to find by email first
        // This handles old email-based usernames that need migration
        if (strpos($username, '@') !== false || preg_match('/[^a-z0-9-]/', $username)) {
            // Try to find by email if it looks like an email
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table WHERE email = %s AND deleted_at IS NULL",
                    $username
                ), ARRAY_A);
                
                if ($data) {
                    // If user has invalid username, trigger migration
                    if (preg_match('/[^a-z0-9-]/', $data['username']) || strpos($data['username'], '@') !== false) {
                        // Generate clean username
                        $new_username = self::generate_unique_username($data['username']);
                        // Update username
                        $wpdb->update(
                            $table,
                            array('username' => $new_username),
                            array('id' => $data['id']),
                            array('%s'),
                            array('%d')
                        );
                        $data['username'] = $new_username;
                    }
                    return new self($data);
                }
            }
            
            // Try exact match (might be URL-encoded)
            $data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE username = %s AND deleted_at IS NULL",
                $username
            ), ARRAY_A);
            
            if ($data) {
                // If user has invalid username, trigger migration
                if (preg_match('/[^a-z0-9-]/', $data['username']) || strpos($data['username'], '@') !== false) {
                    // Generate clean username
                    $new_username = self::generate_unique_username($data['username']);
                    // Update username
                    $wpdb->update(
                        $table,
                        array('username' => $new_username),
                        array('id' => $data['id']),
                        array('%s'),
                        array('%d')
                    );
                    $data['username'] = $new_username;
                }
                return new self($data);
            }
        }
        
        // First try exact username match
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE username = %s AND deleted_at IS NULL",
            $username
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        // If not found and looks like an email, try email lookup
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE email = %s AND deleted_at IS NULL",
                $username
            ), ARRAY_A);
            
            if ($data) {
                return new self($data);
            }
        }
        
        return null;
    }
    
    /**
     * Create new user
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        $defaults = array(
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );
        
        $data = array_merge($defaults, $data);
        
        // Always generate unique username/handle - ensure it's clean and valid
        if (!empty($data['username'])) {
            $data['username'] = self::generate_unique_username($data['username']);
        } else {
            // If no username provided, generate one from email or display name
            $base = !empty($data['email']) ? $data['email'] : (!empty($data['display_name']) ? $data['display_name'] : 'user');
            $data['username'] = self::generate_unique_username($base);
        }
        
        $result = $wpdb->insert(
            $table,
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $user = self::get($wpdb->insert_id);
            
            // Create empty profile
            $profile_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'profiles';
            $wpdb->insert(
                $profile_table,
                array('user_id' => $user->id),
                array('%d')
            );
            
            return $user;
        }
        
        return null;
    }
    
    /**
     * Generate unique username
     * Adds -1, -2, etc. if username already exists
     * Ensures username is URL-safe and not an email address
     */
    public static function generate_unique_username($base_username) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        // If it's an email address, extract the part before @
        if (filter_var($base_username, FILTER_VALIDATE_EMAIL)) {
            $base_username = strtolower(explode('@', $base_username)[0]);
        }
        
        // Sanitize username - only lowercase letters, numbers, and hyphens (NO underscores, NO special chars)
        $base_username = sanitize_user(strtolower($base_username), true);
        $base_username = preg_replace('/[^a-z0-9-]/', '', $base_username); // Only allow lowercase, numbers, and hyphens
        
        // Ensure minimum length of 3 characters
        if (empty($base_username) || strlen($base_username) < 3) {
            $base_username = 'user' . substr(time(), -6); // Use last 6 digits of timestamp
        }
        
        $username = $base_username;
        $counter = 1;
        
        while ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE username = %s",
            $username
        ))) {
            $username = $base_username . '-' . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Check if username is available
     */
    public static function username_available($username, $exclude_user_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
        
        $query = "SELECT id FROM $table WHERE username = %s";
        $params = array($username);
        
        if ($exclude_user_id) {
            $query .= " AND id != %d";
            $params[] = $exclude_user_id;
        }
        
        return !$wpdb->get_var($wpdb->prepare($query, $params));
    }
    
    /**
     * Update user
     */
    public function update($data) {
        $data['updated_at'] = current_time('mysql');
        
        $result = $this->db->update(
            $this->table,
            $data,
            array('id' => $this->id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            $this->populate($data);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update profile
     */
    public function update_profile($data) {
        $profile_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'profiles';
        
        $data['updated_at'] = current_time('mysql');
        
        $exists = $this->db->get_var($this->db->prepare(
            "SELECT id FROM $profile_table WHERE user_id = %d",
            $this->id
        ));
        
        if ($exists) {
            $result = $this->db->update(
                $profile_table,
                $data,
                array('user_id' => $this->id),
                null,
                array('%d')
            );
        } else {
            $data['user_id'] = $this->id;
            $result = $this->db->insert(
                $profile_table,
                $data
            );
        }
        
        if ($result !== false) {
            $this->load_profile();
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete user (soft delete)
     */
    public function delete() {
        return $this->update(array(
            'deleted_at' => current_time('mysql'),
            'status' => 'deleted',
        ));
    }
    
    /**
     * Get user URL
     */
    public function get_url() {
        return home_url('/user/' . $this->username . '/');
    }
    
    /**
     * Get avatar URL - Uses new Simuze_Avatar class with caching
     * Returns appropriate URL variant (thumb, medium, full) based on size
     * Falls back to local default avatar if no avatar exists
     */
    public function get_avatar_url($size = 96, $options = array()) {
        // Support legacy boolean second argument for cache_only
        if (is_bool($options)) {
            $options = array('cache_only' => $options);
        }

        if (!empty($this->simezu_id) && class_exists('Simuze_Avatar')) {
            $avatar = Simuze_Avatar::get_instance();

            // Convert numeric size to variant name
            $variant = 'medium';
            if (is_numeric($size)) {
                if ($size <= 64) {
                    $variant = 'thumb';
                } elseif ($size >= 500) {
                    $variant = 'full';
                } elseif ($size >= 200) {
                    $variant = 'full';
                } else {
                    $variant = 'medium';
                }
            } else {
                $variant = $size; // 'thumb', 'medium', or 'full'
            }

            return $avatar->get_user_avatar_url($this->simezu_id, $variant, $options);
        }

        $avatar = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
        return $avatar ? $avatar->get_default_avatar_url() : get_template_directory_uri() . '/images/profile_default.jpg';
    }
    
    /**
     * Get user's groups
     */
    public function get_groups() {
        global $wpdb;
        $members_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_members';
        $groups_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        
        $groups = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, gm.role, gm.joined_at
            FROM $members_table gm
            INNER JOIN $groups_table g ON gm.group_id = g.id
            WHERE gm.user_id = %d AND gm.status = 'active' AND g.deleted_at IS NULL
            ORDER BY gm.joined_at DESC",
            $this->id
        ), ARRAY_A);
        
        $group_objects = array();
        foreach ($groups as $group_data) {
            $group_objects[] = new Simuze_Group($group_data);
        }
        
        return $group_objects;
    }
    
    /**
     * Get user's activity count
     */
    public function get_activity_count() {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'user_activity';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d",
            $this->id
        ));
    }
    
    /**
     * Get user's friends count
     */
    public function get_friends_count() {
        if (class_exists('Simuze_Friend')) {
            $friends = Simuze_Friend::get_friends($this->id);
            return count($friends);
        }
        return 0;
    }
    
    /**
     * Get user's groups count
     */
    public function get_groups_count() {
        $groups = $this->get_groups();
        return count($groups);
    }
    
    /**
     * Get profile URL helper
     */
    public static function get_profile_url($user_id) {
        $user = self::get($user_id);
        if ($user) {
            return $user->get_url();
        }
        return '';
    }
    
    /**
     * Get avatar helper
     */
    public static function get_avatar($user_id, $width = 96, $height = 96, $class = '') {
        $user = self::get($user_id);
        if ($user) {
            $avatar_url = $user->get_avatar_url($width);
            return sprintf(
                '<img src="%s" width="%d" height="%d" class="%s" alt="%s" />',
                esc_url($avatar_url),
                $width,
                $height,
                esc_attr($class),
                esc_attr($user->display_name)
            );
        }
        return '';
    }
    
    /**
     * Convert to array
     */
    public function to_array() {
        return array(
            'id' => $this->id,
            'simezu_id' => $this->simezu_id,
            'username' => $this->username,
            'email' => $this->email,
            'display_name' => $this->display_name,
            'avatar_url' => $this->get_avatar_url(),
            'url' => $this->get_url(),
            'profile' => $this->profile,
            'stats' => array(
                'activity_count' => $this->get_activity_count(),
                'friends_count' => $this->get_friends_count(),
                'groups_count' => $this->get_groups_count(),
            ),
        );
    }
}

