<?php
/**
 * Group management class
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
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

        if (isset($this->settings) && is_string($this->settings)) {
            $this->settings = json_decode($this->settings, true);
        }
    }

    public static function get($id) {
        if (empty($id)) return null;

        $data = md_db_row("SELECT * FROM simuze_groups WHERE id = ? AND deleted_at IS NULL", [$id]);
        return $data ? new self($data) : null;
    }

    public static function get_by_slug($slug) {
        $data = md_db_row("SELECT * FROM simuze_groups WHERE slug = ? AND deleted_at IS NULL", [$slug]);
        return $data ? new self($data) : null;
    }

    public static function get_by_unique_name($unique_name) {
        $data = md_db_row("SELECT * FROM simuze_groups WHERE unique_name = ? AND deleted_at IS NULL", [$unique_name]);
        return $data ? new self($data) : null;
    }

    public static function create($data) {
        if (empty($data['name'])) {
            error_log('Simuze_Group::create - Missing name field');
            return null;
        }

        if (empty($data['group_type_id'])) {
            error_log('Simuze_Group::create - Missing group_type_id field');
            return null;
        }

        // Generate slug
        if (empty($data['slug'])) {
            $data['slug'] = self::sanitize_slug_with_special_chars($data['name']);
            if (empty($data['slug'])) {
                $data['slug'] = preg_replace('/[^a-z0-9-]+/', '-', strtolower($data['name']));
            }
        } else {
            $data['slug'] = self::sanitize_slug_with_special_chars($data['slug']);
        }

        // Ensure unique slug
        $slug = $data['slug'];
        $counter = 1;
        $max_iter = 1000;
        while (self::slug_exists($slug) && $max_iter-- > 0) {
            $slug = $data['slug'] . '-' . $counter;
            $counter++;
        }
        $data['slug'] = $slug;

        // Generate unique_name
        if (empty($data['unique_name'])) {
            $data['unique_name'] = preg_replace('/[^a-z0-9-]+/', '-', strtolower($data['name']));
        }

        // Ensure unique unique_name
        $unique_name = $data['unique_name'];
        $counter = 1;
        $max_iter = 1000;
        while (self::unique_name_exists($unique_name) && $max_iter-- > 0) {
            $unique_name = $data['unique_name'] . '-' . $counter;
            $counter++;
        }
        $data['unique_name'] = $unique_name;

        // Generate unique_handle
        if (empty($data['unique_handle'])) {
            $data['unique_handle'] = self::slugify_handle($data['name']);
        } else {
            if (!self::validate_handle($data['unique_handle'])) {
                error_log('Simuze_Group::create - Invalid unique_handle format: ' . $data['unique_handle']);
                return null;
            }
        }

        // Ensure unique unique_handle
        $unique_handle = $data['unique_handle'];
        $counter = 1;
        $max_iter = 1000;
        while (self::unique_handle_exists($unique_handle) && $max_iter-- > 0) {
            $unique_handle = $data['unique_handle'] . '-' . $counter;
            $counter++;
        }
        $data['unique_handle'] = $unique_handle;

        $defaults = [
            'status'     => 'public',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $data = array_merge($defaults, $data);

        // Encode settings if array
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }

        // Filter to allowed columns only
        $allowed_columns = [
            'name', 'slug', 'unique_name', 'unique_handle', 'description',
            'group_type_id', 'creator_id', 'owner_id', 'status', 'avatar_url', 'header_url',
            'city', 'province', 'country', 'geo_id', 'lat', 'lng',
            'settings', 'created_at', 'updated_at',
        ];
        $required = ['name', 'group_type_id', 'creator_id'];

        $insert_data = [];
        foreach ($allowed_columns as $col) {
            if (isset($data[$col])) {
                $value = $data[$col];
                if (($col === 'lat' || $col === 'lng') && is_string($value)) {
                    $value = floatval($value);
                }
                // Skip empty non-required values
                if ($value === '' && !in_array($col, $required)) {
                    continue;
                }
                $insert_data[$col] = $value;
            } elseif (in_array($col, $required)) {
                error_log("Simuze_Group::create - Missing required field: $col");
                return null;
            }
        }

        if (empty($insert_data['name']) || empty($insert_data['group_type_id']) || empty($insert_data['creator_id'])) {
            error_log('Simuze_Group::create - Missing required fields after validation');
            return null;
        }

        // Auto-create location groups if location data present
        $location_data = [
            'city'     => $insert_data['city'] ?? null,
            'province' => $insert_data['province'] ?? null,
            'country'  => $insert_data['country'] ?? null,
            'lat'      => $insert_data['lat'] ?? null,
            'lng'      => $insert_data['lng'] ?? null,
            'geo_id'   => $insert_data['geo_id'] ?? null,
        ];
        if (!empty($location_data['country']) || !empty($location_data['province']) || !empty($location_data['city'])) {
            if (class_exists('Simuze_Geo')) {
                Simuze_Geo::auto_create_location_groups($location_data, (int) $insert_data['creator_id']);
            }
        }

        error_log('Simuze_Group::create - Insert data: ' . print_r($insert_data, true));

        $group_id = md_db_insert('simuze_groups', $insert_data);

        if (!$group_id) {
            error_log('Simuze_Group::create - Database insert failed');
            return null;
        }

        $group = self::get($group_id);

        if (!$group) {
            error_log('Simuze_Group::create - Failed to retrieve created group with ID: ' . $group_id);
            return null;
        }

        // Add creator as admin
        if (isset($data['creator_id'])) {
            $member_result = $group->add_member($data['creator_id'], 'admin');
            if (!$member_result) {
                error_log('Simuze_Group::create - Failed to add creator as admin');
            }
        }

        error_log('Simuze_Group::create - Group created successfully with ID: ' . $group->id);
        return $group;
    }

    private static function slug_exists($slug) {
        return (bool) md_db_var("SELECT id FROM simuze_groups WHERE slug = ? AND deleted_at IS NULL", [$slug]);
    }

    public static function unique_name_exists($unique_name, $exclude_id = null) {
        if ($exclude_id) {
            return (bool) md_db_var(
                "SELECT id FROM simuze_groups WHERE unique_name = ? AND id != ? AND deleted_at IS NULL",
                [$unique_name, $exclude_id]
            );
        }
        return (bool) md_db_var(
            "SELECT id FROM simuze_groups WHERE unique_name = ? AND deleted_at IS NULL",
            [$unique_name]
        );
    }

    public static function get_by_unique_handle($handle) {
        // Direct lookup
        $data = md_db_row("SELECT * FROM simuze_groups WHERE unique_handle = ? AND deleted_at IS NULL", [$handle]);
        if ($data) {
            return new self($data);
        }

        // Check aliases
        $alias = md_db_row("SELECT group_id FROM simuze_group_handle_aliases WHERE old_handle = ?", [$handle]);
        if ($alias) {
            $group = self::get($alias['group_id']);
            if ($group) {
                $group->_should_redirect = true;
                $group->_redirect_from   = $handle;
                return $group;
            }
        }

        return null;
    }

    public static function validate_handle($handle) {
        return (bool) preg_match('/^[a-z0-9-]{3,64}$/', $handle);
    }

    public static function unique_handle_exists($handle, $exclude_id = null) {
        if ($exclude_id) {
            $exists = md_db_var(
                "SELECT id FROM simuze_groups WHERE unique_handle = ? AND id != ? AND deleted_at IS NULL",
                [$handle, $exclude_id]
            );
        } else {
            $exists = md_db_var(
                "SELECT id FROM simuze_groups WHERE unique_handle = ? AND deleted_at IS NULL",
                [$handle]
            );
        }

        if ($exists) return true;

        return (bool) md_db_var("SELECT id FROM simuze_group_handle_aliases WHERE old_handle = ?", [$handle]);
    }

    /**
     * Sanitize slug with special character handling
     */
    public static function sanitize_slug_with_special_chars($text) {
        if (empty($text)) return '';

        $text = strtolower($text);

        $special_chars = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ñ' => 'n', 'ç' => 'c',
            'æ' => 'ae', 'œ' => 'oe', 'ß' => 'ss',
            'ð' => 'd', 'þ' => 'th',
        ];

        $text = strtr($text, $special_chars);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = preg_replace('/[^a-z0-9-]/', '', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');

        return $text;
    }

    public static function slugify_handle($text) {
        return self::sanitize_slug_with_special_chars($text);
    }

    public function add_handle_alias($old_handle) {
        $exists = md_db_var("SELECT id FROM simuze_group_handle_aliases WHERE old_handle = ?", [$old_handle]);
        if (!$exists) {
            md_db_insert('simuze_group_handle_aliases', [
                'group_id'   => $this->id,
                'old_handle' => $old_handle,
            ]);
        }
    }

    public function update($data, $options = []) {
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }

        // Validate unique_name if provided
        if (!empty($data['unique_name'])) {
            $unique_name = preg_replace('/[^a-z0-9-]+/', '-', strtolower($data['unique_name']));
            $counter  = 1;
            $original = $unique_name;
            $max_iter = 1000;
            while (self::unique_name_exists($unique_name, $this->id) && $max_iter-- > 0) {
                $unique_name = $original . '-' . $counter;
                $counter++;
            }
            $data['unique_name'] = $unique_name;
        }

        // Handle unique_handle update
        if (!empty($data['unique_handle'])) {
            $old_handle = $this->unique_handle;
            $new_handle = $data['unique_handle'];

            if (!self::validate_handle($new_handle)) {
                error_log('Simuze_Group::update - Invalid unique_handle format: ' . $new_handle);
                return false;
            }

            if ($old_handle && $old_handle !== $new_handle) {
                if (self::unique_handle_exists($new_handle, $this->id)) {
                    error_log('Simuze_Group::update - unique_handle already exists: ' . $new_handle);
                    return false;
                }
                $this->add_handle_alias($old_handle);
            } elseif (!$old_handle) {
                $counter  = 1;
                $original = $new_handle;
                $max_iter = 1000;
                while (self::unique_handle_exists($new_handle, $this->id) && $max_iter-- > 0) {
                    $new_handle = $original . '-' . $counter;
                    $counter++;
                }
            }

            $data['unique_handle'] = $new_handle;
        }

        // Auto-create location groups if location data present (skip when e.g. admin save to avoid timeout)
        $skip_location = !empty($options['skip_location_groups']);
        if (!$skip_location) {
            $location_data = [
                'city'     => $data['city'] ?? $this->city,
                'province' => $data['province'] ?? $this->province,
                'country'  => $data['country'] ?? $this->country,
                'lat'      => $data['lat'] ?? $this->lat,
                'lng'      => $data['lng'] ?? $this->lng,
                'geo_id'   => $data['geo_id'] ?? $this->geo_id,
            ];
            if (!empty($location_data['country']) || !empty($location_data['province']) || !empty($location_data['city'])) {
                if (class_exists('Simuze_Geo')) {
                    Simuze_Geo::auto_create_location_groups($location_data, $this->creator_id);
                }
            }
        }

        $result = md_db_update('simuze_groups', $data, ['id' => $this->id]);

        if ($result !== false) {
            $this->populate($data);
            return true;
        }

        return false;
    }

    public function delete() {
        $ts = time();
        return $this->update([
            'slug'          => $this->slug . '-deleted-' . $ts,
            'unique_name'   => $this->unique_name   ? $this->unique_name   . '-deleted-' . $ts : null,
            'unique_handle' => $this->unique_handle ? $this->unique_handle . '-deleted-' . $ts : null,
            'deleted_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function add_member($user_id, $role = 'member') {
        $exists = md_db_var(
            "SELECT id FROM simuze_group_members WHERE group_id = ? AND user_id = ?",
            [$this->id, $user_id]
        );

        if ($exists) {
            return md_db_update(
                'simuze_group_members',
                ['role' => $role, 'status' => 'active'],
                ['group_id' => $this->id, 'user_id' => $user_id]
            );
        }

        return md_db_insert('simuze_group_members', [
            'group_id'  => $this->id,
            'user_id'   => $user_id,
            'role'      => $role,
            'status'    => 'active',
            'joined_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function remove_member($user_id) {
        return md_db_delete('simuze_group_members', ['group_id' => $this->id, 'user_id' => $user_id]);
    }

    public function get_members($args = []) {
        $defaults = ['role' => null, 'status' => 'active', 'per_page' => 20, 'page' => 1];
        $args     = array_merge($defaults, $args);

        $where  = 'gm.group_id = ? AND gm.status = ?';
        $params = [$this->id, $args['status']];

        if ($args['role']) {
            $where    .= ' AND gm.role = ?';
            $params[]  = $args['role'];
        }

        $limit  = (int) $args['per_page'];
        $offset = (int) (($args['page'] - 1) * $args['per_page']);

        $members = md_db_query(
            "SELECT u.*, gm.role, gm.joined_at
            FROM simuze_group_members gm
            INNER JOIN simuze_users u ON gm.user_id = u.id
            WHERE {$where}
            ORDER BY gm.joined_at DESC
            LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        $count = (int) md_db_var("SELECT COUNT(*) FROM simuze_group_members gm WHERE {$where}", $params);

        return ['members' => $members, 'count' => $count];
    }

    public function is_member($user_id, $role = null) {
        $where  = "group_id = ? AND user_id = ? AND status = 'active'";
        $params = [$this->id, $user_id];

        if ($role) {
            $where    .= ' AND role = ?';
            $params[]  = $role;
        }

        return (bool) md_db_var("SELECT id FROM simuze_group_members WHERE {$where}", $params);
    }

    public function get_url() {
        $group_type_data = $this->get_group_type();
        $group_type      = $group_type_data ? $group_type_data['slug'] : 'artist';

        $location_types = ['country', 'city', 'province'];
        if (in_array($group_type, $location_types)) {
            $sanitized_slug = self::sanitize_slug_with_special_chars($this->slug);
            return md_home_url('/' . $group_type . '/' . $sanitized_slug . '/');
        }

        if (!empty($this->unique_handle)) {
            return md_home_url('/' . $this->unique_handle . '/');
        }

        if (!empty($this->unique_name)) {
            return md_home_url('/' . $this->unique_name . '/');
        }

        $sanitized_slug = self::sanitize_slug_with_special_chars($this->slug);
        return md_home_url('/' . $group_type . '/' . $sanitized_slug . '/');
    }

    public function get_group_type() {
        if (!$this->group_type_id) return null;
        return md_db_row("SELECT * FROM simuze_group_types WHERE id = ?", [$this->group_type_id]);
    }

    public function can_upload_music() {
        $type = $this->get_group_type();
        if (!$type) return false;
        return in_array(strtolower($type['slug']), ['artist', 'label']);
    }

    public function get_type_slug() {
        $type = $this->get_group_type();
        return $type ? strtolower($type['slug']) : null;
    }

    public function send_invitation($user_id, $invited_by, $message = null) {
        $exists = md_db_var(
            "SELECT id FROM simuze_group_invitations WHERE group_id = ? AND user_id = ? AND status = 'pending'",
            [$this->id, $user_id]
        );

        if ($exists) return false;

        $result = md_db_insert('simuze_group_invitations', [
            'group_id'   => $this->id,
            'user_id'    => $user_id,
            'invited_by' => $invited_by,
            'message'    => $message,
            'status'     => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($result) {
            $this->send_invitation_email($user_id, $invited_by, $message);
            if (class_exists('Simuze_Notification')) {
                Simuze_Notification::group_invite($user_id, $this->id, $invited_by);
            }
        }

        return $result;
    }

    private function send_invitation_email($user_id, $invited_by, $personal_message = null) {
        $invited_user = Simuze_User::get($user_id);
        $inviter      = Simuze_User::get($invited_by);

        if (!$invited_user || !$invited_user->email) return false;

        $group_name   = $this->name;
        $group_url    = $this->get_url();
        $inviter_name = $inviter ? ($inviter->display_name ?: $inviter->username) : 'Someone';

        $subject = sprintf("You've been invited to join %s on Simuze", $group_name);

        $email_html = $this->build_invitation_email_html(
            $invited_user->display_name ?: $invited_user->username,
            $group_name,
            $group_url,
            $inviter_name,
            $personal_message
        );

        $admin_email = md_db_var("SELECT option_value FROM settings WHERE option_name = 'admin_email' LIMIT 1") ?? '';
        $headers = "Content-Type: text/html; charset=UTF-8\r\nFrom: Simuze <{$admin_email}>";

        return mail($invited_user->email, $subject, $email_html, $headers);
    }

    private function build_invitation_email_html($recipient_name, $group_name, $group_url, $inviter_name, $personal_message = null) {
        $site_name    = md_db_var("SELECT option_value FROM settings WHERE option_name = 'site_name' LIMIT 1") ?? 'Simuze';
        $site_url     = md_home_url('/');
        $button_color = '#a8d5ba';

        $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $h($site_name); ?> - Group Invitation</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background-color: #1a1a1a; padding: 30px 20px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: 0.05em;"><?php echo $h($site_name); ?></h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.7); font-size: 14px;">Build scenes. Not profiles.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #1a1a1a; font-size: 24px; font-weight: 600;">You've been invited!</h2>
                            <p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6;">Hi <?php echo $h($recipient_name); ?>,</p>
                            <p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6;">
                                <strong><?php echo $h($inviter_name); ?></strong> has invited you to join the group <strong><?php echo $h($group_name); ?></strong> on <?php echo $h($site_name); ?>.
                            </p>
                            <?php if ($personal_message): ?>
                            <div style="background-color: #f9f9f9; border-left: 4px solid <?php echo $h($button_color); ?>; padding: 15px 20px; margin: 20px 0; border-radius: 4px;">
                                <p style="margin: 0; color: #333333; font-size: 15px; line-height: 1.6; font-style: italic;">"<?php echo $h($personal_message); ?>"</p>
                            </div>
                            <?php endif; ?>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="<?php echo $h($group_url); ?>" style="display: inline-block; background-color: <?php echo $h($button_color); ?>; color: #1a1a1a; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-size: 16px; font-weight: 600; text-align: center;">
                                            View Group &amp; Accept Invitation
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 20px 0 0 0; color: #666666; font-size: 14px; line-height: 1.6;">
                                Or copy and paste this link into your browser:<br>
                                <a href="<?php echo $h($group_url); ?>" style="color: <?php echo $h($button_color); ?>; text-decoration: underline;"><?php echo $h($group_url); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f9f9f9; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 10px 0; color: #666666; font-size: 14px;">This invitation was sent to <?php echo $h($recipient_name); ?>.</p>
                            <p style="margin: 0; color: #999999; font-size: 12px;">&copy; <?php echo date('Y'); ?> <?php echo $h($site_name); ?>. All rights reserved.</p>
                            <p style="margin: 10px 0 0 0; color: #999999; font-size: 12px;">
                                <a href="<?php echo $h($site_url); ?>" style="color: #999999; text-decoration: none;">Visit <?php echo $h($site_name); ?></a>
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

    public function get_invitations($status = 'pending') {
        return md_db_query(
            "SELECT i.*, u.username, u.display_name, u.avatar_url
            FROM simuze_group_invitations i
            INNER JOIN simuze_users u ON i.user_id = u.id
            WHERE i.group_id = ? AND i.status = ?
            ORDER BY i.created_at DESC",
            [$this->id, $status]
        );
    }

    public function accept_invitation($user_id) {
        md_db_update(
            'simuze_group_invitations',
            ['status' => 'accepted', 'updated_at' => date('Y-m-d H:i:s')],
            ['group_id' => $this->id, 'user_id' => $user_id, 'status' => 'pending']
        );
        return $this->add_member($user_id);
    }

    public function reject_invitation($user_id) {
        return md_db_update(
            'simuze_group_invitations',
            ['status' => 'rejected', 'updated_at' => date('Y-m-d H:i:s')],
            ['group_id' => $this->id, 'user_id' => $user_id, 'status' => 'pending']
        );
    }

    public function cancel_invitation($invitation_id) {
        $invitation = md_db_row(
            "SELECT * FROM simuze_group_invitations WHERE id = ? AND group_id = ?",
            [$invitation_id, $this->id]
        );
        if (!$invitation) return false;
        return md_db_delete('simuze_group_invitations', ['id' => $invitation_id, 'group_id' => $this->id]);
    }

    public function can_manage($user_id) {
        if ($this->creator_id == $user_id) return true;
        return $this->is_member($user_id, 'admin') || $this->is_member($user_id, 'mod');
    }

    public function get_member_count() {
        return (int) md_db_var(
            "SELECT COUNT(*) FROM simuze_group_members WHERE group_id = ? AND status = 'active'",
            [$this->id]
        );
    }

    public function update_member_role($user_id, $role) {
        return md_db_update(
            'simuze_group_members',
            ['role' => $role],
            ['group_id' => $this->id, 'user_id' => $user_id]
        );
    }

    public function ban_member($user_id) {
        return $this->update_member_role($user_id, 'banned');
    }

    public function unban_member($user_id) {
        return $this->update_member_role($user_id, 'member');
    }

    public static function findByTypeAndSlug(string $type_slug, string $slug): ?self {
        $row = md_db_row(
            "SELECT g.*, gt.slug AS type_slug
             FROM simuze_groups g
             INNER JOIN simuze_group_types gt ON g.group_type_id = gt.id
             WHERE gt.slug = ? AND g.slug = ? AND g.deleted_at IS NULL
             LIMIT 1",
            [$type_slug, $slug]
        );
        if (!$row) return null;
        try {
            return new self($row);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function get_all($args = []) {
        $defaults = ['per_page' => 20, 'page' => 1, 'group_type' => null, 'status' => 'public', 'search' => null];
        $args     = array_merge($defaults, $args);

        $where  = 'deleted_at IS NULL';
        $params = [];

        if ($args['group_type']) {
            $type_id = md_db_var("SELECT id FROM simuze_group_types WHERE slug = ?", [$args['group_type']]);
            if ($type_id) {
                $where    .= ' AND group_type_id = ?';
                $params[]  = $type_id;
            }
        }

        if ($args['status']) {
            $where    .= ' AND status = ?';
            $params[]  = $args['status'];
        }

        if ($args['search']) {
            $like      = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $args['search']) . '%';
            $where    .= ' AND (name LIKE ? OR description LIKE ?)';
            $params[]  = $like;
            $params[]  = $like;
        }

        $limit  = (int) $args['per_page'];
        $offset = (int) (($args['page'] - 1) * $args['per_page']);

        $groups = md_db_query(
            "SELECT * FROM simuze_groups WHERE {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        $total = (int) md_db_var("SELECT COUNT(*) FROM simuze_groups WHERE {$where}", $params);

        $group_objects = [];
        foreach ($groups as $group_data) {
            $group_objects[] = new self($group_data);
        }

        return ['groups' => $group_objects, 'total' => $total];
    }

    public function to_array() {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'unique_name'  => $this->unique_name,
            'unique_handle' => $this->unique_handle,
            'description'  => $this->description,
            'group_type'   => $this->get_group_type(),
            'creator_id'   => $this->creator_id,
            'avatar_url'   => $this->get_avatar_url(),
            'header_url'   => $this->header_url,
            'status'       => $this->status,
            'location'     => [
                'city'     => $this->city,
                'province' => $this->province,
                'country'  => $this->country,
                'geo_id'   => $this->geo_id,
                'lat'      => $this->lat,
                'lng'      => $this->lng,
            ],
            'member_count' => $this->get_member_count(),
            'url'          => $this->get_url(),
            'created_at'   => $this->created_at,
        ];
    }

    public static function get_user_groups($user_id, $limit = 20) {
        $limit = (int) $limit;
        return md_db_query(
            "SELECT g.*, gm.role as member_role, gm.joined_at
            FROM simuze_groups g
            INNER JOIN simuze_group_members gm ON g.id = gm.group_id
            WHERE gm.user_id = ? AND g.deleted_at IS NULL AND gm.status = 'active'
            ORDER BY gm.joined_at DESC
            LIMIT {$limit}",
            [$user_id]
        );
    }

    public static function count_user_groups($user_id) {
        return (int) md_db_var(
            "SELECT COUNT(*)
            FROM simuze_groups g
            INNER JOIN simuze_group_members gm ON g.id = gm.group_id
            WHERE gm.user_id = ? AND g.deleted_at IS NULL AND gm.status = 'active'",
            [$user_id]
        );
    }

    public static function get_following_groups($user_id, $limit = 20) {
        $limit = (int) $limit;
        return md_db_query(
            "SELECT g.*, f.created_at as followed_at
            FROM simuze_groups g
            INNER JOIN simuze_follows f ON g.id = f.followable_id
            WHERE f.follower_id = ? AND f.followable_type = 'group' AND g.deleted_at IS NULL
            ORDER BY f.created_at DESC
            LIMIT {$limit}",
            [$user_id]
        );
    }

    public static function get_user_invitations($user_id) {
        return md_db_query(
            "SELECT g.*, gi.id as invitation_id, gi.inviter_id, gi.message, gi.created_at as invited_at
            FROM simuze_groups g
            INNER JOIN simuze_group_invitations gi ON g.id = gi.group_id
            WHERE gi.user_id = ? AND gi.status = 'pending' AND g.deleted_at IS NULL
            ORDER BY gi.created_at DESC",
            [$user_id]
        );
    }

    public static function get_group_types() {
        return md_db_query(
            "SELECT * FROM simuze_group_types
            WHERE deleted_at IS NULL
            AND slug NOT IN ('city', 'province', 'country', 'state')
            ORDER BY name ASC"
        );
    }

    public static function add_member_static($group_id, $user_id, $role = 'member') {
        $exists = md_db_var(
            "SELECT id FROM simuze_group_members WHERE group_id = ? AND user_id = ?",
            [$group_id, $user_id]
        );

        if ($exists) {
            return md_db_update(
                'simuze_group_members',
                ['role' => $role, 'status' => 'active'],
                ['group_id' => $group_id, 'user_id' => $user_id]
            );
        }

        return md_db_insert('simuze_group_members', [
            'group_id'  => $group_id,
            'user_id'   => $user_id,
            'role'      => $role,
            'status'    => 'active',
            'joined_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function get_avatar_url($size = 'medium') {
        // 1) Custom uploads via Simuze_Avatar (non-default) take precedence.
        if (class_exists('Simuze_Avatar')) {
            $avatar      = Simuze_Avatar::get_instance();
            $default_url = $avatar->get_default_group_avatar_url();
            $url         = $avatar->get_group_avatar_url($this->id, $size);
            if (!empty($url) && $url !== $default_url) {
                return $url;
            }
        }

        // 2) Persisted URL (e.g. LocationAvatar JPEG under public/uploads/simuze-groups/).
        //    Do not use live map tile URLs here — they break caching and are fragile in the browser.
        if (!empty($this->avatar_url)) {
            if ($size === 'thumb') {
                if (strpos($this->avatar_url, 'simuze/groups') !== false && strpos($this->avatar_url, '/avatar/') !== false) {
                    if (strpos($this->avatar_url, '/thumb.jpg') !== false) {
                        return $this->avatar_url;
                    }
                    $thumb_url = str_replace('/full.jpg', '/thumb.jpg', $this->avatar_url);
                    $thumb_url = str_replace('/medium.jpg', '/thumb.jpg', $thumb_url);
                    return $thumb_url;
                }
            }
            return $this->avatar_url;
        }

        $upload     = md_upload_dir();
        $avatar_dir = rtrim($upload['basedir'], '/') . '/simuze/groups/' . $this->id . '/avatar';
        $avatar_base = rtrim($upload['baseurl'], '/') . '/simuze/groups/' . $this->id . '/avatar';

        if (is_dir($avatar_dir)) {
            if ($size === 'thumb' || (is_numeric($size) && intval($size) <= 64)) {
                $size_file = 'thumb.jpg';
            } elseif ($size === 'full' || (is_numeric($size) && intval($size) >= 500)) {
                $size_file = 'full.jpg';
            } else {
                $size_file = 'medium.jpg';
            }

            $file_path = $avatar_dir . '/' . $size_file;
            if (file_exists($file_path)) {
                $file_url = $avatar_base . '/' . $size_file;
                if (class_exists('Simuze_Avatar')) {
                    $avatar = Simuze_Avatar::get_instance();
                    $urls   = [];
                    foreach (['thumb', 'medium', 'full'] as $s) {
                        if (file_exists($avatar_dir . '/' . $s . '.jpg')) {
                            $urls[$s] = $avatar_base . '/' . $s . '.jpg';
                        }
                    }
                    if (!empty($urls)) {
                        $avatar->update_group_cache($this->id, $urls, ['source' => 'simuze_local', 'auto_discovered' => true]);
                    }
                }
                return $file_url;
            }
        }

        return class_exists('Simuze_Avatar')
            ? Simuze_Avatar::get_instance()->get_default_group_avatar_url()
            : md_home_url('/assets/images/default-group.svg');
    }
}
