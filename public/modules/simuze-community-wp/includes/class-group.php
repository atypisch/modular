<?php
/**
 * Group management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Group {
    
    public $id;
    public $name;
    public $slug;
    public $unique_name;
    public $unique_handle;
    public $description;
    public $group_type_id;
    public $creator_id;
    public $avatar_url;
    public $header_url;
    public $status;
    public $city;
    public $province;
    public $country;
    public $geo_id;
    public $lat;
    public $lng;
    public $settings;
    public $created_at;
    public $updated_at;
    
    private $db;
    private $table;
    
    /**
     * Constructor
     */
    public function __construct($data = null) {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        
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
        
        // Decode settings JSON
        if (isset($this->settings) && is_string($this->settings)) {
            $this->settings = json_decode($this->settings, true);
        }
    }
    
    /**
     * Get group by ID
     */
    public static function get($id) {
        if (empty($id)) {
            return null;
        }
        
        global $wpdb;
        if (!$wpdb) {
            error_log('Simuze_Group::get() - ERROR: $wpdb is not available!');
            return null;
        }
        
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        
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
     * Get group by slug
     */
    public static function get_by_slug($slug) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE slug = %s AND deleted_at IS NULL",
            $slug
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Get group by unique_name
     */
    public static function get_by_unique_name($unique_name) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE unique_name = %s AND deleted_at IS NULL",
            $unique_name
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Create new group
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        
        // Validate required fields
        if (empty($data['name'])) {
            error_log('Simuze_Group::create - Missing name field');
            return null;
        }
        
        if (empty($data['group_type_id'])) {
            error_log('Simuze_Group::create - Missing group_type_id field');
            return null;
        }
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            // Use sanitize_slug_with_special_chars for better special character handling
            $data['slug'] = self::sanitize_slug_with_special_chars($data['name']);
            // Fallback to sanitize_title if empty (for edge cases)
            if (empty($data['slug'])) {
                $data['slug'] = sanitize_title($data['name']);
            }
        } else {
            // Sanitize provided slug to handle special characters
            $data['slug'] = self::sanitize_slug_with_special_chars($data['slug']);
        }
        
        // Ensure unique slug
        $slug = $data['slug'];
        $counter = 1;
        while (self::slug_exists($slug)) {
            $slug = $data['slug'] . '-' . $counter;
            $counter++;
        }
        $data['slug'] = $slug;
        
        // Generate unique_name if not provided
        if (empty($data['unique_name'])) {
            $data['unique_name'] = sanitize_title($data['name']);
        }
        
        // Ensure unique unique_name
        $unique_name = $data['unique_name'];
        $counter = 1;
        while (self::unique_name_exists($unique_name)) {
            $unique_name = $data['unique_name'] . '-' . $counter;
            $counter++;
        }
        $data['unique_name'] = $unique_name;
        
        // Generate unique_handle if not provided
        if (empty($data['unique_handle'])) {
            $data['unique_handle'] = self::slugify_handle($data['name']);
        } else {
            // Validate provided handle
            if (!self::validate_handle($data['unique_handle'])) {
                error_log('Simuze_Group::create - Invalid unique_handle format: ' . $data['unique_handle']);
                return null;
            }
        }
        
        // Ensure unique unique_handle
        $unique_handle = $data['unique_handle'];
        $counter = 1;
        while (self::unique_handle_exists($unique_handle)) {
            $unique_handle = $data['unique_handle'] . '-' . $counter;
            $counter++;
        }
        $data['unique_handle'] = $unique_handle;
        
        $defaults = array(
            'status' => 'public',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );
        
        $data = array_merge($defaults, $data);
        
        // Encode settings if array
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }
        
        // Prepare data for insert - only include valid columns
        $allowed_columns = array(
            'name' => array('format' => '%s', 'required' => true),
            'slug' => array('format' => '%s', 'required' => true),
            'unique_name' => array('format' => '%s', 'required' => false),
            'unique_handle' => array('format' => '%s', 'required' => false),
            'description' => array('format' => '%s', 'required' => false),
            'group_type_id' => array('format' => '%d', 'required' => true),
            'creator_id' => array('format' => '%d', 'required' => true),
            'status' => array('format' => '%s', 'required' => false),
            'avatar_url' => array('format' => '%s', 'required' => false),
            'header_url' => array('format' => '%s', 'required' => false),
            'city' => array('format' => '%s', 'required' => false),
            'province' => array('format' => '%s', 'required' => false),
            'country' => array('format' => '%s', 'required' => false),
            'geo_id' => array('format' => '%s', 'required' => false),
            'lat' => array('format' => '%f', 'required' => false),
            'lng' => array('format' => '%f', 'required' => false),
            'settings' => array('format' => '%s', 'required' => false),
            'created_at' => array('format' => '%s', 'required' => false),
            'updated_at' => array('format' => '%s', 'required' => false)
        );
        
        $insert_data = array();
        $insert_format = array();
        
        foreach ($allowed_columns as $column => $config) {
            $format = $config['format'];
            $required = $config['required'];
            
            if (isset($data[$column])) {
                $value = $data[$column];
                
                // Convert lat/lng to float if they're strings
                if (($column === 'lat' || $column === 'lng') && is_string($value)) {
                    $value = floatval($value);
                }
                
                // Skip empty values unless required
                if ($value === '' && !$required) {
                    continue;
                }
                
                $insert_data[$column] = $value;
                $insert_format[] = $format;
            } elseif ($required) {
                error_log("Simuze_Group::create - Missing required field: $column");
                return null;
            }
        }
        
        // Ensure required fields are present
        if (empty($insert_data['name'])) {
            error_log('Simuze_Group::create - Missing required field: name');
            return null;
        }
        
        if (empty($insert_data['group_type_id'])) {
            error_log('Simuze_Group::create - Missing required field: group_type_id');
            return null;
        }
        
        if (empty($insert_data['creator_id'])) {
            error_log('Simuze_Group::create - Missing required field: creator_id');
            return null;
        }
        
        error_log('Simuze_Group::create - Insert data: ' . print_r($insert_data, true));
        error_log('Simuze_Group::create - Insert format: ' . print_r($insert_format, true));
        
        $result = $wpdb->insert($table, $insert_data, $insert_format);
        
        if ($result === false) {
            error_log('Simuze_Group::create - Database insert failed');
            error_log('Simuze_Group::create - Last error: ' . $wpdb->last_error);
            error_log('Simuze_Group::create - Last query: ' . $wpdb->last_query);
            return null;
        }
        
        if ($result) {
            $group = self::get($wpdb->insert_id);
            
            if (!$group) {
                error_log('Simuze_Group::create - Failed to retrieve created group with ID: ' . $wpdb->insert_id);
                return null;
            }
            
            // Add creator as admin
            if ($group && isset($data['creator_id'])) {
                $member_result = $group->add_member($data['creator_id'], 'admin');
                if (!$member_result) {
                    error_log('Simuze_Group::create - Failed to add creator as admin');
                }
            }
            
            error_log('Simuze_Group::create - Group created successfully with ID: ' . $group->id);
            return $group;
        }
        
        return null;
    }
    
    /**
     * Check if slug exists
     */
    private static function slug_exists($slug) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE slug = %s AND deleted_at IS NULL",
            $slug
        ));
        
        return (bool) $exists;
    }
    
    /**
     * Check if unique_name exists
     */
    public static function unique_name_exists($unique_name, $exclude_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        
        if ($exclude_id) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE unique_name = %s AND id != %d AND deleted_at IS NULL",
                $unique_name,
                $exclude_id
            ));
        } else {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE unique_name = %s AND deleted_at IS NULL",
                $unique_name
            ));
        }
        
        return (bool) $exists;
    }
    
    /**
     * Get group by unique_handle (with alias lookup)
     */
    public static function get_by_unique_handle($handle) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        $aliases_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_handle_aliases';
        
        // First try direct lookup
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE unique_handle = %s AND deleted_at IS NULL",
            $handle
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        // If not found, check aliases table
        $alias = $wpdb->get_row($wpdb->prepare(
            "SELECT group_id FROM $aliases_table WHERE old_handle = %s",
            $handle
        ), ARRAY_A);
        
        if ($alias) {
            // Redirect: return the group but mark it for redirect
            $group = self::get($alias['group_id']);
            if ($group) {
                $group->_should_redirect = true;
                $group->_redirect_from = $handle;
                return $group;
            }
        }
        
        return null;
    }
    
    /**
     * Validate unique_handle format
     */
    public static function validate_handle($handle) {
        // Must be 3-64 characters, lowercase letters, numbers, and hyphens only
        if (!preg_match('/^[a-z0-9-]{3,64}$/', $handle)) {
            return false;
        }
        return true;
    }
    
    /**
     * Check if unique_handle exists (including aliases)
     */
    public static function unique_handle_exists($handle, $exclude_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        $aliases_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_handle_aliases';
        
        // Check in groups table
        if ($exclude_id) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE unique_handle = %s AND id != %d AND deleted_at IS NULL",
                $handle,
                $exclude_id
            ));
        } else {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE unique_handle = %s AND deleted_at IS NULL",
                $handle
            ));
        }
        
        if ($exists) {
            return true;
        }
        
        // Check in aliases table
        $alias_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $aliases_table WHERE old_handle = %s",
            $handle
        ));
        
        return (bool) $alias_exists;
    }
    
    /**
     * Slugify helper for handles
     */
    /**
     * Sanitize slug with special character handling
     * Converts special characters to base characters (e.g., ô -> o, é -> e)
     */
    public static function sanitize_slug_with_special_chars($text) {
        if (empty($text)) {
            return '';
        }
        
        // Convert to lowercase
        $text = strtolower($text);
        
        // Map special characters to base characters
        $special_chars = array(
            // Accented characters
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ñ' => 'n', 'ç' => 'c',
            // Other special characters
            'æ' => 'ae', 'œ' => 'oe', 'ß' => 'ss',
            'ð' => 'd', 'þ' => 'th',
        );
        
        // Replace special characters
        $text = strtr($text, $special_chars);
        
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
    
    public static function slugify_handle($text) {
        // Use the new sanitize function for consistency
        return self::sanitize_slug_with_special_chars($text);
    }
    
    /**
     * Add handle alias
     */
    public function add_handle_alias($old_handle) {
        global $wpdb;
        $aliases_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_handle_aliases';
        
        // Check if alias already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $aliases_table WHERE old_handle = %s",
            $old_handle
        ));
        
        if (!$exists) {
            $wpdb->insert(
                $aliases_table,
                array(
                    'group_id' => $this->id,
                    'old_handle' => $old_handle
                ),
                array('%d', '%s')
            );
        }
    }
    
    /**
     * Update group
     */
    public function update($data) {
        $data['updated_at'] = current_time('mysql');
        
        // Encode settings if array
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }
        
        // Validate unique_name if provided
        if (isset($data['unique_name']) && !empty($data['unique_name'])) {
            $unique_name = sanitize_title($data['unique_name']);
            
            // Check if unique_name is already taken by another group
            if (self::unique_name_exists($unique_name, $this->id)) {
                // Generate unique version
                $counter = 1;
                $original_unique_name = $unique_name;
                while (self::unique_name_exists($unique_name, $this->id)) {
                    $unique_name = $original_unique_name . '-' . $counter;
                    $counter++;
                }
            }
            
            $data['unique_name'] = $unique_name;
        }
        
        // Handle unique_handle update
        if (isset($data['unique_handle']) && !empty($data['unique_handle'])) {
            $old_handle = $this->unique_handle;
            $new_handle = $data['unique_handle'];
            
            // Validate format
            if (!self::validate_handle($new_handle)) {
                error_log('Simuze_Group::update - Invalid unique_handle format: ' . $new_handle);
                return false;
            }
            
            // Check if handle is being changed
            if ($old_handle && $old_handle !== $new_handle) {
                // Check if new handle is available
                if (self::unique_handle_exists($new_handle, $this->id)) {
                    error_log('Simuze_Group::update - unique_handle already exists: ' . $new_handle);
                    return false;
                }
                
                // Save old handle as alias
                if ($old_handle) {
                    $this->add_handle_alias($old_handle);
                }
            } elseif (!$old_handle) {
                // First time setting handle, ensure uniqueness
                $counter = 1;
                $original_handle = $new_handle;
                while (self::unique_handle_exists($new_handle, $this->id)) {
                    $new_handle = $original_handle . '-' . $counter;
                    $counter++;
                }
            }
            
            $data['unique_handle'] = $new_handle;
        }
        
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
     * Delete group (soft delete)
     * Also modifies slug to free it up for reuse
     */
    public function delete() {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        
        // Modify slug to free it up for reuse (add timestamp to make it unique)
        $deleted_slug = $this->slug . '-deleted-' . time();
        
        // Also modify unique_name and unique_handle if they exist
        $deleted_unique_name = $this->unique_name ? $this->unique_name . '-deleted-' . time() : null;
        $deleted_unique_handle = $this->unique_handle ? $this->unique_handle . '-deleted-' . time() : null;
        
        return $this->update(array(
            'slug' => $deleted_slug,
            'unique_name' => $deleted_unique_name,
            'unique_handle' => $deleted_unique_handle,
            'deleted_at' => current_time('mysql'),
        ));
    }
    
    /**
     * Add member to group
     */
    public function add_member($user_id, $role = 'member') {
        $members_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_members';
        
        // Check if already member
        $exists = $this->db->get_var($this->db->prepare(
            "SELECT id FROM $members_table WHERE group_id = %d AND user_id = %d",
            $this->id,
            $user_id
        ));
        
        if ($exists) {
            // Update role
            return $this->db->update(
                $members_table,
                array('role' => $role, 'status' => 'active'),
                array('group_id' => $this->id, 'user_id' => $user_id),
                array('%s', '%s'),
                array('%d', '%d')
            );
        }
        
        // Insert new member
        return $this->db->insert(
            $members_table,
            array(
                'group_id' => $this->id,
                'user_id' => $user_id,
                'role' => $role,
                'status' => 'active',
                'joined_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Remove member from group
     */
    public function remove_member($user_id) {
        $members_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_members';
        
        return $this->db->delete(
            $members_table,
            array('group_id' => $this->id, 'user_id' => $user_id),
            array('%d', '%d')
        );
    }
    
    /**
     * Get group members
     */
    public function get_members($args = array()) {
        $members_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_members';
        $users_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'users';
        
        $defaults = array(
            'role' => null,
            'status' => 'active',
            'per_page' => 20,
            'page' => 1,
        );
        
        $args = array_merge($defaults, $args);
        
        $where = array("gm.group_id = %d", "gm.status = %s");
        $where_values = array($this->id, $args['status']);
        
        if ($args['role']) {
            $where[] = "gm.role = %s";
            $where_values[] = $args['role'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $query = $this->db->prepare(
            "SELECT u.*, gm.role, gm.joined_at 
            FROM $members_table gm
            INNER JOIN $users_table u ON gm.user_id = u.id
            WHERE $where_clause
            ORDER BY gm.joined_at DESC
            LIMIT %d OFFSET %d",
            array_merge($where_values, array($args['per_page'], $offset))
        );
        
        $members = $this->db->get_results($query, ARRAY_A);
        
        $count = $this->db->get_var($this->db->prepare(
            "SELECT COUNT(*) FROM $members_table gm WHERE $where_clause",
            $where_values
        ));
        
        return array(
            'members' => $members,
            'count' => (int) $count,
        );
    }
    
    /**
     * Check if user is member
     */
    public function is_member($user_id, $role = null) {
        $members_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_members';
        
        $where = array("group_id = %d", "user_id = %d", "status = 'active'");
        $where_values = array($this->id, $user_id);
        
        if ($role) {
            $where[] = "role = %s";
            $where_values[] = $role;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $exists = $this->db->get_var($this->db->prepare(
            "SELECT id FROM $members_table WHERE $where_clause",
            $where_values
        ));
        
        return (bool) $exists;
    }
    
    /**
     * Get group URL
     * Uses unique_name if available, otherwise falls back to group-type/slug format
     * For countries, cities, and provinces, always use group-type/slug format
     */
    public function get_url() {
        $group_type_data = $this->get_group_type();
        $group_type = $group_type_data ? $group_type_data['slug'] : 'artist';
        
        // For location-based groups (country, city, province), always use group-type/slug format
        $location_types = array('country', 'city', 'province');
        if (in_array($group_type, $location_types)) {
            // Sanitize slug to handle special characters
            $sanitized_slug = self::sanitize_slug_with_special_chars($this->slug);
            return home_url('/' . $group_type . '/' . $sanitized_slug . '/');
        }
        
        // For other group types, use unique_handle if available (new system)
        if (!empty($this->unique_handle)) {
            return home_url('/' . $this->unique_handle . '/');
        }
        
        // Fallback to unique_name (legacy)
        if (!empty($this->unique_name)) {
            return home_url('/' . $this->unique_name . '/');
        }
        
        // Fallback to old format for backwards compatibility
        $sanitized_slug = self::sanitize_slug_with_special_chars($this->slug);
        return home_url('/' . $group_type . '/' . $sanitized_slug . '/');
    }
    
    /**
     * Get group type
     */
    public function get_group_type() {
        if (!$this->group_type_id) {
            return null;
        }
        
        $types_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_types';
        $type = $this->db->get_row($this->db->prepare(
            "SELECT * FROM $types_table WHERE id = %d",
            $this->group_type_id
        ), ARRAY_A);
        
        return $type;
    }
    
    /**
     * Check if group can upload music
     * Only Artist and Label group types can upload
     */
    public function can_upload_music() {
        $type = $this->get_group_type();
        
        if (!$type) {
            return false;
        }
        
        // Allowed group types for music upload
        $allowed_types = array('artist', 'label');
        
        return in_array(strtolower($type['slug']), $allowed_types);
    }
    
    /**
     * Get group type slug
     */
    public function get_type_slug() {
        $type = $this->get_group_type();
        return $type ? strtolower($type['slug']) : null;
    }
    
    /**
     * Send invitation to user
     */
    public function send_invitation($user_id, $invited_by, $message = null) {
        $invitations_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_invitations';
        
        // Check if invitation already exists
        $exists = $this->db->get_var($this->db->prepare(
            "SELECT id FROM $invitations_table WHERE group_id = %d AND user_id = %d AND status = 'pending'",
            $this->id,
            $user_id
        ));
        
        if ($exists) {
            return false; // Already invited
        }
        
        $data = array(
            'group_id' => $this->id,
            'user_id' => $user_id,
            'invited_by' => $invited_by,
            'message' => $message,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        );
        
        $result = $this->db->insert($invitations_table, $data);
        
        // Send email notification if invitation was successfully created
        if ($result) {
            $this->send_invitation_email($user_id, $invited_by, $message);
        }
        
        return $result;
    }
    
    /**
     * Send invitation email to user
     */
    private function send_invitation_email($user_id, $invited_by, $personal_message = null) {
        $invited_user = Simuze_User::get($user_id);
        $inviter = Simuze_User::get($invited_by);
        
        if (!$invited_user || !$invited_user->email) {
            return false; // Can't send email without user email
        }
        
        $group_name = $this->name;
        $group_url = $this->get_url();
        $inviter_name = $inviter ? ($inviter->display_name ?: $inviter->username) : 'Someone';
        
        $subject = sprintf(__('You\'ve been invited to join %s on Simuze', 'simuze'), $group_name);
        
        // Build HTML email
        $email_html = $this->build_invitation_email_html(
            $invited_user->display_name ?: $invited_user->username,
            $group_name,
            $group_url,
            $inviter_name,
            $personal_message
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Simuze <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($invited_user->email, $subject, $email_html, $headers);
    }
    
    /**
     * Build HTML email template for group invitation
     */
    private function build_invitation_email_html($recipient_name, $group_name, $group_url, $inviter_name, $personal_message = null) {
        $site_name = get_bloginfo('name');
        $site_url = home_url('/');
        $button_color = '#a8d5ba'; // Simuze green color
        $button_hover = '#8fc5a8';
        
        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($site_name); ?> - Group Invitation</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <!-- Email Container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #1a1a1a; padding: 30px 20px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: 0.05em;">
                                <?php echo esc_html($site_name); ?>
                            </h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.7); font-size: 14px;">
                                Build scenes. Not profiles.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #1a1a1a; font-size: 24px; font-weight: 600;">
                                You've been invited!
                            </h2>
                            
                            <p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6;">
                                Hi <?php echo esc_html($recipient_name); ?>,
                            </p>
                            
                            <p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6;">
                                <strong><?php echo esc_html($inviter_name); ?></strong> has invited you to join the group <strong><?php echo esc_html($group_name); ?></strong> on <?php echo esc_html($site_name); ?>.
                            </p>
                            
                            <?php if ($personal_message): ?>
                            <div style="background-color: #f9f9f9; border-left: 4px solid <?php echo esc_attr($button_color); ?>; padding: 15px 20px; margin: 20px 0; border-radius: 4px;">
                                <p style="margin: 0; color: #333333; font-size: 15px; line-height: 1.6; font-style: italic;">
                                    "<?php echo esc_html($personal_message); ?>"
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="<?php echo esc_url($group_url); ?>" style="display: inline-block; background-color: <?php echo esc_attr($button_color); ?>; color: #1a1a1a; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-size: 16px; font-weight: 600; text-align: center;">
                                            View Group & Accept Invitation
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 20px 0 0 0; color: #666666; font-size: 14px; line-height: 1.6;">
                                Or copy and paste this link into your browser:<br>
                                <a href="<?php echo esc_url($group_url); ?>" style="color: <?php echo esc_attr($button_color); ?>; text-decoration: underline;"><?php echo esc_url($group_url); ?></a>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9f9f9; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 10px 0; color: #666666; font-size: 14px;">
                                This invitation was sent to <?php echo esc_html($recipient_name); ?>.
                            </p>
                            <p style="margin: 0; color: #999999; font-size: 12px;">
                                &copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.
                            </p>
                            <p style="margin: 10px 0 0 0; color: #999999; font-size: 12px;">
                                <a href="<?php echo esc_url($site_url); ?>" style="color: #999999; text-decoration: none;">Visit <?php echo esc_html($site_name); ?></a>
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get pending invitations
     */
    public function get_invitations($status = 'pending') {
        $invitations_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_invitations';
        $users_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'users';
        
        return $this->db->get_results($this->db->prepare(
            "SELECT i.*, u.username, u.display_name, u.avatar_url
            FROM $invitations_table i
            INNER JOIN $users_table u ON i.user_id = u.id
            WHERE i.group_id = %d AND i.status = %s
            ORDER BY i.created_at DESC",
            $this->id,
            $status
        ), ARRAY_A);
    }
    
    /**
     * Accept invitation
     */
    public function accept_invitation($user_id) {
        $invitations_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_invitations';
        
        // Update invitation status
        $this->db->update(
            $invitations_table,
            array('status' => 'accepted', 'updated_at' => current_time('mysql')),
            array('group_id' => $this->id, 'user_id' => $user_id, 'status' => 'pending'),
            array('%s', '%s'),
            array('%d', '%d', '%s')
        );
        
        // Add user as member
        return $this->add_member($user_id);
    }
    
    /**
     * Reject invitation
     */
    public function reject_invitation($user_id) {
        $invitations_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_invitations';
        
        return $this->db->update(
            $invitations_table,
            array('status' => 'rejected', 'updated_at' => current_time('mysql')),
            array('group_id' => $this->id, 'user_id' => $user_id, 'status' => 'pending'),
            array('%s', '%s'),
            array('%d', '%d', '%s')
        );
    }
    
    /**
     * Cancel/delete invitation by invitation ID
     */
    public function cancel_invitation($invitation_id) {
        $invitations_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_invitations';
        
        // Verify the invitation belongs to this group
        $invitation = $this->db->get_row($this->db->prepare(
            "SELECT * FROM $invitations_table WHERE id = %d AND group_id = %d",
            $invitation_id,
            $this->id
        ));
        
        if (!$invitation) {
            return false;
        }
        
        // Delete the invitation
        return $this->db->delete(
            $invitations_table,
            array('id' => $invitation_id, 'group_id' => $this->id),
            array('%d', '%d')
        );
    }
    
    /**
     * Check if user can manage group
     */
    public function can_manage($user_id) {
        // Creator can always manage
        if ($this->creator_id == $user_id) {
            return true;
        }
        
        // Admins and mods can manage
        return $this->is_member($user_id, 'admin') || $this->is_member($user_id, 'mod');
    }
    
    /**
     * Get member count
     */
    public function get_member_count() {
        $members_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_members';
        
        return (int) $this->db->get_var($this->db->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE group_id = %d AND status = 'active'",
            $this->id
        ));
    }
    
    /**
     * Update member role
     */
    public function update_member_role($user_id, $role) {
        $members_table = $this->db->prefix . SIMUZE_DB_PREFIX . 'group_members';
        
        return $this->db->update(
            $members_table,
            array('role' => $role),
            array('group_id' => $this->id, 'user_id' => $user_id),
            array('%s'),
            array('%d', '%d')
        );
    }
    
    /**
     * Ban member
     */
    public function ban_member($user_id) {
        return $this->update_member_role($user_id, 'banned');
    }
    
    /**
     * Unban member
     */
    public function unban_member($user_id) {
        return $this->update_member_role($user_id, 'member');
    }
    
    /**
     * Get all groups (static)
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'group_type' => null,
            'status' => 'public',
            'search' => null,
        );
        
        $args = array_merge($defaults, $args);
        
        $where = "deleted_at IS NULL";
        $where_values = array();
        
        if ($args['group_type']) {
            $types_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_types';
            $type_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $types_table WHERE slug = %s",
                $args['group_type']
            ));
            
            if ($type_id) {
                $where .= " AND group_type_id = %d";
                $where_values[] = $type_id;
            }
        }
        
        if ($args['status']) {
            $where .= " AND status = %s";
            $where_values[] = $args['status'];
        }
        
        if ($args['search']) {
            $where .= " AND (name LIKE %s OR description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $query = "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $args['per_page'];
        $where_values[] = $offset;
        
        $groups = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        
        $count_query = "SELECT COUNT(*) FROM $table WHERE $where";
        $count = $wpdb->get_var($wpdb->prepare($count_query, array_slice($where_values, 0, -2)));
        
        $group_objects = array();
        foreach ($groups as $group_data) {
            $group_objects[] = new self($group_data);
        }
        
        return array(
            'groups' => $group_objects,
            'total' => (int) $count,
        );
    }
    
    /**
     * Convert to array
     */
    public function to_array() {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'unique_name' => $this->unique_name,
            'unique_handle' => $this->unique_handle,
            'description' => $this->description,
            'group_type' => $this->get_group_type(),
            'creator_id' => $this->creator_id,
            'avatar_url' => $this->get_avatar_url(),
            'header_url' => $this->header_url,
            'status' => $this->status,
            'location' => array(
                'city' => $this->city,
                'province' => $this->province,
                'country' => $this->country,
                'geo_id' => $this->geo_id,
                'lat' => $this->lat,
                'lng' => $this->lng,
            ),
            'member_count' => $this->get_member_count(),
            'url' => $this->get_url(),
            'created_at' => $this->created_at,
        );
    }
    
    /**
     * Get groups for a user (member of)
     */
    public static function get_user_groups($user_id, $limit = 20) {
        global $wpdb;
        $groups_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        $members_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_members';
        
        $groups = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, gm.role as member_role, gm.joined_at
            FROM $groups_table g
            INNER JOIN $members_table gm ON g.id = gm.group_id
            WHERE gm.user_id = %d AND g.deleted_at IS NULL AND gm.status = 'active'
            ORDER BY gm.joined_at DESC
            LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);
        
        return $groups;
    }
    
    /**
     * Count groups for a user
     */
    public static function count_user_groups($user_id) {
        global $wpdb;
        $groups_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        $members_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_members';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM $groups_table g
            INNER JOIN $members_table gm ON g.id = gm.group_id
            WHERE gm.user_id = %d AND g.deleted_at IS NULL AND gm.status = 'active'",
            $user_id
        ));
    }
    
    /**
     * Get groups user is following
     */
    public static function get_following_groups($user_id, $limit = 20) {
        global $wpdb;
        $groups_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        $follows_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'follows';
        
        $groups = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, f.created_at as followed_at
            FROM $groups_table g
            INNER JOIN $follows_table f ON g.id = f.followable_id
            WHERE f.follower_id = %d AND f.followable_type = 'group' AND g.deleted_at IS NULL
            ORDER BY f.created_at DESC
            LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);
        
        return $groups;
    }
    
    /**
     * Get group invitations for user
     */
    public static function get_user_invitations($user_id) {
        global $wpdb;
        $groups_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        $invitations_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_invitations';
        
        $invitations = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, gi.id as invitation_id, gi.inviter_id, gi.message, gi.created_at as invited_at
            FROM $groups_table g
            INNER JOIN $invitations_table gi ON g.id = gi.group_id
            WHERE gi.user_id = %d AND gi.status = 'pending' AND g.deleted_at IS NULL
            ORDER BY gi.created_at DESC",
            $user_id
        ), ARRAY_A);
        
        return $invitations;
    }
    
    /**
     * Get all group types
     */
    public static function get_group_types() {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_types';
        
        // Exclude automatic location-based group types (city, province, country)
        return $wpdb->get_results(
            "SELECT * FROM $table 
            WHERE deleted_at IS NULL 
            AND slug NOT IN ('city', 'province', 'country', 'state')
            ORDER BY name ASC",
            ARRAY_A
        );
    }
    
    /**
     * Static add_member method for use without instance
     */
    public static function add_member_static($group_id, $user_id, $role = 'member') {
        global $wpdb;
        $members_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_members';
        
        // Check if already member
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $members_table WHERE group_id = %d AND user_id = %d",
            $group_id,
            $user_id
        ));
        
        if ($exists) {
            return $wpdb->update(
                $members_table,
                array('role' => $role, 'status' => 'active'),
                array('group_id' => $group_id, 'user_id' => $user_id),
                array('%s', '%s'),
                array('%d', '%d')
            );
        }
        
        return $wpdb->insert(
            $members_table,
            array(
                'group_id' => $group_id,
                'user_id' => $user_id,
                'role' => $role,
                'status' => 'active',
                'joined_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get avatar URL - Uses new Simuze_Avatar class with caching
     * Returns appropriate URL variant (thumb, medium, full) based on size
     * Falls back to local default avatar if no avatar exists
     */
    public function get_avatar_url($size = 'medium') {
        // 1) Custom uploads / cache via Simuze_Avatar (non-default) take precedence.
        if (class_exists('Simuze_Avatar')) {
            $avatar = Simuze_Avatar::get_instance();
            $default_url = $avatar->get_default_group_avatar_url();
            $url = $avatar->get_group_avatar_url($this->id, $size);
            // Only use cached URL if it's not the default (meaning we found a real avatar)
            if (!empty($url) && $url !== $default_url) {
                return $url;
            }
        }

        // 2) Persisted URL (e.g. LocationAvatar JPEG). Do not use live OSM tile URLs here.
        // Legacy support if a direct avatar_url is stored on the group record
        // This handles cases where avatar was uploaded before cache system
        if (!empty($this->avatar_url)) {
            // If requesting thumb size and we have a full URL, try to find thumb version
            if ($size === 'thumb') {
                $path_info = pathinfo($this->avatar_url);
                // Check if URL contains 'simuze/groups' path structure
                if (strpos($this->avatar_url, 'simuze/groups') !== false && strpos($this->avatar_url, '/avatar/') !== false) {
                    // Construct thumb URL from full URL path
                    $thumb_url = str_replace('/full.jpg', '/thumb.jpg', $this->avatar_url);
                    $thumb_url = str_replace('/medium.jpg', '/thumb.jpg', $thumb_url);
                    // If it's already a thumb, return as is
                    if (strpos($this->avatar_url, '/thumb.jpg') !== false) {
                        return $this->avatar_url;
                    }
                    return $thumb_url;
                }
            }
            // For medium/full or if thumb construction failed, return the stored URL
            return $this->avatar_url;
        }

        // Check if avatar files exist on disk even without cache/db entry
        // This handles cases where files were uploaded but cache wasn't updated
        $upload_dir = wp_upload_dir();
        $avatar_dir = trailingslashit($upload_dir['basedir']) . 'simuze/groups/' . $this->id . '/avatar';
        $avatar_url_base = trailingslashit($upload_dir['baseurl']) . 'simuze/groups/' . $this->id . '/avatar';
        
        if (is_dir($avatar_dir)) {
            $size_file = '';
            if ($size === 'thumb' || (is_numeric($size) && intval($size) <= 64)) {
                $size_file = 'thumb.jpg';
            } elseif ($size === 'full' || (is_numeric($size) && intval($size) >= 500)) {
                $size_file = 'full.jpg';
            } else {
                $size_file = 'medium.jpg';
            }
            
            $file_path = trailingslashit($avatar_dir) . $size_file;
            if (file_exists($file_path)) {
                $file_url = trailingslashit($avatar_url_base) . $size_file;
                // Update cache for future requests
                if (class_exists('Simuze_Avatar')) {
                    $avatar = Simuze_Avatar::get_instance();
                    // Check if other sizes exist too
                    $urls = array();
                    if (file_exists(trailingslashit($avatar_dir) . 'thumb.jpg')) {
                        $urls['thumb'] = trailingslashit($avatar_url_base) . 'thumb.jpg';
                    }
                    if (file_exists(trailingslashit($avatar_dir) . 'medium.jpg')) {
                        $urls['medium'] = trailingslashit($avatar_url_base) . 'medium.jpg';
                    }
                    if (file_exists(trailingslashit($avatar_dir) . 'full.jpg')) {
                        $urls['full'] = trailingslashit($avatar_url_base) . 'full.jpg';
                    }
                    if (!empty($urls)) {
                        $avatar->update_group_cache($this->id, $urls, array('source' => 'simuze_local', 'auto_discovered' => true));
                    }
                }
                return $file_url;
            }
        }

        // Final fallback to default avatar
        $fallback = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance()->get_default_group_avatar_url() : get_template_directory_uri() . '/images/default-group.svg';
        return $fallback;
    }
}

