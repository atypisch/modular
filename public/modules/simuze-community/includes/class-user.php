<?php
/**
 * User management class
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_User {

    public $id;
    public $simezu_id;
    public $username;
    public $unique_identifier;
    public $email;
    public $display_name;
    public $avatar_url;
    public $header_url;
    public $status;
    public $created_at;
    public $updated_at;
    public $profile;

    public function __construct($data = null) {
        if ($data) {
            $this->populate($data);
        }
    }

    private function populate($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        if ($this->id) {
            $this->load_profile();
        }
    }

    private function load_profile() {
        $profile = md_db_row("SELECT * FROM simuze_profiles WHERE user_id = ?", [$this->id]);

        if ($profile) {
            if (!empty($profile['social_links'])) {
                $social_links = json_decode($profile['social_links'], true);
                if (is_array($social_links) && isset($social_links['websites'])) {
                    $profile['websites'] = $social_links['websites'];
                }
            }
            if (!isset($profile['websites']) || !is_array($profile['websites'])) {
                $profile['websites'] = [];
            }
            $this->profile = $profile;
            if (!empty($profile['unique_id'])) {
                $this->unique_identifier = $profile['unique_id'];
            }
        } else {
            $this->profile = null;
        }
    }

    public static function get($id) {
        $data = md_db_row("SELECT * FROM simuze_users WHERE id = ? AND deleted_at IS NULL", [$id]);
        return $data ? new self($data) : null;
    }

    public static function get_by_simezu_id($simezu_id) {
        $data = md_db_row("SELECT * FROM simuze_users WHERE simezu_id = ? AND deleted_at IS NULL", [$simezu_id]);
        return $data ? new self($data) : null;
    }

    public static function get_by_email($email) {
        $data = md_db_row("SELECT * FROM simuze_users WHERE email = ? AND deleted_at IS NULL", [$email]);
        return $data ? new self($data) : null;
    }

    public static function get_by_username($username) {
        // If it looks like an email, try email lookup first
        if (strpos($username, '@') !== false || preg_match('/[^a-z0-9-]/', $username)) {
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $data = md_db_row("SELECT * FROM simuze_users WHERE email = ? AND deleted_at IS NULL", [$username]);
                if ($data) {
                    if (preg_match('/[^a-z0-9-]/', $data['username']) || strpos($data['username'], '@') !== false) {
                        $new_username = self::generate_unique_username($data['username']);
                        md_db_update('simuze_users', ['username' => $new_username], ['id' => $data['id']]);
                        $data['username'] = $new_username;
                    }
                    return new self($data);
                }
            }

            $data = md_db_row("SELECT * FROM simuze_users WHERE username = ? AND deleted_at IS NULL", [$username]);
            if ($data) {
                if (preg_match('/[^a-z0-9-]/', $data['username']) || strpos($data['username'], '@') !== false) {
                    $new_username = self::generate_unique_username($data['username']);
                    md_db_update('simuze_users', ['username' => $new_username], ['id' => $data['id']]);
                    $data['username'] = $new_username;
                }
                return new self($data);
            }
        }

        // Exact username match
        $data = md_db_row("SELECT * FROM simuze_users WHERE username = ? AND deleted_at IS NULL", [$username]);
        if ($data) return new self($data);

        // Email fallback
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $data = md_db_row("SELECT * FROM simuze_users WHERE email = ? AND deleted_at IS NULL", [$username]);
            if ($data) return new self($data);
        }

        return null;
    }

    public static function create($data) {
        $now = date('Y-m-d H:i:s');
        $data = array_merge(['status' => 'active', 'created_at' => $now, 'updated_at' => $now], $data);

        if (!empty($data['username'])) {
            $data['username'] = self::generate_unique_username($data['username']);
        } else {
            $base            = $data['email'] ?? $data['display_name'] ?? 'user';
            $data['username'] = self::generate_unique_username($base);
        }

        $user_id = md_db_insert('simuze_users', $data);

        if ($user_id) {
            $user = self::get($user_id);
            md_db_insert('simuze_profiles', ['user_id' => $user->id]);
            return $user;
        }

        return null;
    }

    public static function generate_unique_username($base_username) {
        if (filter_var($base_username, FILTER_VALIDATE_EMAIL)) {
            $base_username = strtolower(explode('@', $base_username)[0]);
        }

        $base_username = preg_replace('/[^a-z0-9-]/', '', strtolower($base_username));

        if (empty($base_username) || strlen($base_username) < 3) {
            $base_username = 'user' . substr(time(), -6);
        }

        $username = $base_username;
        $counter  = 1;

        while (md_db_var("SELECT id FROM simuze_users WHERE username = ?", [$username])) {
            $username = $base_username . '-' . $counter;
            $counter++;
        }

        return $username;
    }

    public static function username_available($username, $exclude_user_id = null) {
        if ($exclude_user_id) {
            return !md_db_var("SELECT id FROM simuze_users WHERE username = ? AND id != ?", [$username, $exclude_user_id]);
        }
        return !md_db_var("SELECT id FROM simuze_users WHERE username = ?", [$username]);
    }

    public function update($data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = md_db_update('simuze_users', $data, ['id' => $this->id]);

        if ($result !== false) {
            $this->populate($data);
            return true;
        }

        return false;
    }

    public function update_profile($data) {
        $data['updated_at'] = date('Y-m-d H:i:s');

        $exists = md_db_var("SELECT id FROM simuze_profiles WHERE user_id = ?", [$this->id]);

        if ($exists) {
            $result = md_db_update('simuze_profiles', $data, ['user_id' => $this->id]);
        } else {
            $data['user_id'] = $this->id;
            $result          = md_db_insert('simuze_profiles', $data);
        }

        if ($result !== false) {
            $this->load_profile();
            return true;
        }

        return false;
    }

    public function delete() {
        return $this->update(['deleted_at' => date('Y-m-d H:i:s'), 'status' => 'deleted']);
    }

    public function get_url() {
        return md_home_url('/user/' . $this->username . '/');
    }

    public function get_avatar_url($size = 96, $options = []) {
        if (is_bool($options)) {
            $options = ['cache_only' => $options];
        }

        if (!empty($this->simezu_id) && class_exists('Simuze_Avatar')) {
            $avatar  = Simuze_Avatar::get_instance();
            $variant = 'medium';
            if (is_numeric($size)) {
                $variant = $size <= 64 ? 'thumb' : ($size >= 200 ? 'full' : 'medium');
            } else {
                $variant = $size;
            }
            return $avatar->get_user_avatar_url($this->simezu_id, $variant, $options);
        }

        $avatar = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
        return $avatar ? $avatar->get_default_avatar_url() : md_asset_url('images/profile_default.jpg');
    }

    public function get_groups() {
        $groups = md_db_query(
            "SELECT g.*, gm.role, gm.joined_at
             FROM simuze_group_members gm
             INNER JOIN simuze_groups g ON gm.group_id = g.id
             WHERE gm.user_id = ? AND gm.status = 'active' AND g.deleted_at IS NULL
             ORDER BY gm.joined_at DESC",
            [$this->id]
        );

        $group_objects = [];
        foreach ($groups as $group_data) {
            $group_objects[] = new Simuze_Group($group_data);
        }
        return $group_objects;
    }

    public function get_activity_count() {
        return (int) md_db_var("SELECT COUNT(*) FROM simuze_user_activity WHERE user_id = ?", [$this->id]);
    }

    public function get_friends_count() {
        if (class_exists('Simuze_Friend')) {
            return count(Simuze_Friend::get_friends($this->id));
        }
        return 0;
    }

    public function get_groups_count() {
        return count($this->get_groups());
    }

    public static function get_profile_url($user_id) {
        $user = self::get($user_id);
        return $user ? $user->get_url() : '';
    }

    public static function get_avatar($user_id, $width = 96, $height = 96, $class = '') {
        $user = self::get($user_id);
        if ($user) {
            return sprintf(
                '<img src="%s" width="%d" height="%d" class="%s" alt="%s" />',
                htmlspecialchars($user->get_avatar_url($width), ENT_QUOTES, 'UTF-8'),
                $width,
                $height,
                htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($user->display_name, ENT_QUOTES, 'UTF-8')
            );
        }
        return '';
    }

    public function to_array() {
        return [
            'id'           => $this->id,
            'simezu_id'    => $this->simezu_id,
            'username'     => $this->username,
            'email'        => $this->email,
            'display_name' => $this->display_name,
            'avatar_url'   => $this->get_avatar_url(),
            'url'          => $this->get_url(),
            'profile'      => $this->profile,
            'stats'        => [
                'activity_count' => $this->get_activity_count(),
                'friends_count'  => $this->get_friends_count(),
                'groups_count'   => $this->get_groups_count(),
            ],
        ];
    }
}
