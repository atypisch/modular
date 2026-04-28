<?php
/**
 * Group Edit Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
$group = Simuze_Group::get($group_id);

if (!$group) {
    wp_die('Group not found.');
}

// Handle form submission
if (isset($_POST['update_group']) && check_admin_referer('update-group_' . $group_id)) {
    $data = array(
        'name' => sanitize_text_field($_POST['name']),
        'description' => wp_kses_post($_POST['description']),
        'status' => sanitize_text_field($_POST['status']),
        'city' => sanitize_text_field($_POST['city']),
        'province' => sanitize_text_field($_POST['province']),
        'country' => sanitize_text_field($_POST['country']),
        'lat' => isset($_POST['lat']) ? floatval($_POST['lat']) : null,
        'lng' => isset($_POST['lng']) ? floatval($_POST['lng']) : null,
    );
    
    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $upload = wp_handle_upload($_FILES['avatar'], array('test_form' => false));
        if (!isset($upload['error'])) {
            $data['avatar_url'] = $upload['url'];
        }
    }
    
    // Handle header upload
    if (isset($_FILES['header']) && $_FILES['header']['error'] === UPLOAD_ERR_OK) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $upload = wp_handle_upload($_FILES['header'], array('test_form' => false));
        if (!isset($upload['error'])) {
            $data['header_url'] = $upload['url'];
        }
    }
    
    // Update group type
    if (isset($_POST['group_type_id'])) {
        $data['group_type_id'] = intval($_POST['group_type_id']);
    }
    
    $result = $group->update($data);
    
    if ($result) {
        echo '<div class="notice notice-success is-dismissible"><p>Group updated successfully!</p></div>';
        // Reload group data
        $group = Simuze_Group::get($group_id);
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Failed to update group.</p></div>';
    }
}

// Get group type
$group_type_data = $group->get_group_type();

// Get all group types
global $wpdb;
$types_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_types';
$all_group_types = $wpdb->get_results("SELECT * FROM $types_table WHERE deleted_at IS NULL ORDER BY name ASC", ARRAY_A);

// Get creator
$creator = Simuze_User::get($group->creator_id);

// Get members
$members_result = $group->get_members(array('per_page' => 100));
$members = $members_result['members'];
?>

<div class="wrap">
    <h1>Edit Group: <?php echo esc_html($group->name); ?></h1>
    
    <form method="post" enctype="multipart/form-data" id="group-edit-form">
        <?php wp_nonce_field('update-group_' . $group_id); ?>
        <input type="hidden" name="update_group" value="1">
        
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <!-- Basic Information -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Basic Information</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><label for="name">Group Name</label></th>
                                    <td>
                                        <input type="text" id="name" name="name" value="<?php echo esc_attr($group->name); ?>" class="regular-text" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="description">Description</label></th>
                                    <td>
                                        <?php
                                        wp_editor(
                                            $group->description,
                                            'description',
                                            array(
                                                'textarea_name' => 'description',
                                                'textarea_rows' => 10,
                                                'media_buttons' => false,
                                            )
                                        );
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="group_type_id">Group Type</label></th>
                                    <td>
                                        <select id="group_type_id" name="group_type_id">
                                            <option value="">— Select —</option>
                                            <?php foreach ($all_group_types as $type): ?>
                                                <option value="<?php echo esc_attr($type['id']); ?>" 
                                                        <?php selected($group->group_type_id, $type['id']); ?>>
                                                    <?php echo esc_html($type['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="status">Status</label></th>
                                    <td>
                                        <select id="status" name="status">
                                            <option value="public" <?php selected($group->status, 'public'); ?>>Public</option>
                                            <option value="private" <?php selected($group->status, 'private'); ?>>Private</option>
                                            <option value="hidden" <?php selected($group->status, 'hidden'); ?>>Hidden</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Location Information -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Location</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><label for="city">City</label></th>
                                    <td>
                                        <input type="text" id="city" name="city" value="<?php echo esc_attr($group->city); ?>" class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="province">Province</label></th>
                                    <td>
                                        <input type="text" id="province" name="province" value="<?php echo esc_attr($group->province); ?>" class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="country">Country</label></th>
                                    <td>
                                        <input type="text" id="country" name="country" value="<?php echo esc_attr($group->country); ?>" class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="lat">Latitude</label></th>
                                    <td>
                                        <input type="number" id="lat" name="lat" value="<?php echo esc_attr($group->lat); ?>" step="any" class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="lng">Longitude</label></th>
                                    <td>
                                        <input type="number" id="lng" name="lng" value="<?php echo esc_attr($group->lng); ?>" step="any" class="regular-text">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Members -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Members (<?php echo count($members); ?>)</span></h2>
                        <div class="inside">
                            <?php if (!empty($members)): ?>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Role</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($members as $member): ?>
                                            <?php $member_user = Simuze_User::get($member['user_id']); ?>
                                            <tr>
                                                <td>
                                                    <?php if ($member_user): ?>
                                                        <strong><?php echo esc_html($member_user->display_name); ?></strong>
                                                        <br><small>@<?php echo esc_html($member_user->username); ?></small>
                                                    <?php else: ?>
                                                        User ID: <?php echo esc_html($member['user_id']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <select class="member-role-select" data-user-id="<?php echo esc_attr($member['user_id']); ?>" data-group-id="<?php echo esc_attr($group->id); ?>">
                                                        <option value="member" <?php selected($member['role'], 'member'); ?>>Member</option>
                                                        <option value="mod" <?php selected($member['role'], 'mod'); ?>>Moderator</option>
                                                        <option value="admin" <?php selected($member['role'], 'admin'); ?>>Admin</option>
                                                    </select>
                                                </td>
                                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($member['joined_at']))); ?></td>
                                                <td>
                                                    <button type="button" class="button button-small remove-member-btn" 
                                                            data-user-id="<?php echo esc_attr($member['user_id']); ?>" 
                                                            data-group-id="<?php echo esc_attr($group->id); ?>">
                                                        Remove
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No members yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div id="postbox-container-1" class="postbox-container">
                    <!-- Images -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Images</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><label for="avatar">Avatar</label></th>
                                    <td>
                                        <?php if ($group->avatar_url): ?>
                                            <img src="<?php echo esc_url($group->avatar_url); ?>" alt="Avatar" style="max-width: 150px; margin-bottom: 10px; display: block;">
                                        <?php endif; ?>
                                        <input type="file" id="avatar" name="avatar" accept="image/*">
                                        <p class="description">Recommended size: 200x200px</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="header">Header Image</label></th>
                                    <td>
                                        <?php if ($group->header_url): ?>
                                            <img src="<?php echo esc_url($group->header_url); ?>" alt="Header" style="max-width: 100%; margin-bottom: 10px; display: block;">
                                        <?php endif; ?>
                                        <input type="file" id="header" name="header" accept="image/*">
                                        <p class="description">Recommended size: 1200x300px</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Group Information -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Group Information</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th>Group ID</th>
                                    <td><?php echo esc_html($group->id); ?></td>
                                </tr>
                                <tr>
                                    <th>Slug</th>
                                    <td><?php echo esc_html($group->slug); ?></td>
                                </tr>
                                <tr>
                                    <th>Creator</th>
                                    <td>
                                        <?php if ($creator): ?>
                                            <a href="<?php echo admin_url('admin.php?page=simuze-users&action=edit&user_id=' . $creator->id); ?>">
                                                <?php echo esc_html($creator->display_name); ?>
                                            </a>
                                        <?php else: ?>
                                            User ID: <?php echo esc_html($group->creator_id); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created</th>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($group->created_at))); ?></td>
                                </tr>
                                <tr>
                                    <th>Updated</th>
                                    <td><?php echo esc_html($group->updated_at ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($group->updated_at)) : '—'); ?></td>
                                </tr>
                                <tr>
                                    <th>Member Count</th>
                                    <td><?php echo esc_html($group->get_member_count()); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="postbox">
                        <h2 class="hndle"><span>Actions</span></h2>
                        <div class="inside">
                            <p>
                                <button type="submit" class="button button-primary button-large">Update Group</button>
                            </p>
                            <p>
                                <a href="<?php echo esc_url($group->get_url()); ?>" class="button" target="_blank">View Group</a>
                                <a href="<?php echo admin_url('admin.php?page=simuze-groups'); ?>" class="button">Back to Groups</a>
                            </p>
                            <p>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=simuze-groups&action=delete&group_id=' . $group->id), 'delete-group_' . $group->id); ?>" 
                                   class="button button-link-delete" 
                                   onclick="return confirm('Are you sure you want to delete this group? This action cannot be undone.');">
                                    Delete Group
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Store original values for role selects
    $('.member-role-select').each(function() {
        $(this).data('original-value', $(this).val());
    });
    
    // Update member role
    $('.member-role-select').on('change', function() {
        var $select = $(this);
        var userId = $select.data('user-id');
        var groupId = $select.data('group-id');
        var newRole = $select.val();
        var originalValue = $select.data('original-value');
        
        // Use admin-ajax for admin context
        var apiUrl = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.apiUrl) 
            ? simuzeCommunity.apiUrl 
            : '<?php echo esc_js(rest_url("simuze/v1/")); ?>';
        var nonce = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.nonce) 
            ? simuzeCommunity.nonce 
            : '<?php echo esc_js(wp_create_nonce("wp_rest")); ?>';
        
        $.ajax({
            url: apiUrl + 'groups/' + groupId + '/update-member',
            method: 'POST',
            data: JSON.stringify({
                user_id: userId,
                role: newRole
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(response) {
                $select.data('original-value', newRole);
                alert('Member role updated successfully!');
            },
            error: function(error) {
                alert('Failed to update member role');
                // Revert selection
                $select.val($select.data('original-value'));
            }
        });
    });
    
    // Remove member
    $('.remove-member-btn').on('click', function() {
        if (!confirm('Are you sure you want to remove this member?')) {
            return;
        }
        
        var $btn = $(this);
        var userId = $btn.data('user-id');
        var groupId = $btn.data('group-id');
        
        // Use admin-ajax for admin context
        var apiUrl = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.apiUrl) 
            ? simuzeCommunity.apiUrl 
            : '<?php echo esc_js(rest_url("simuze/v1/")); ?>';
        var nonce = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.nonce) 
            ? simuzeCommunity.nonce 
            : '<?php echo esc_js(wp_create_nonce("wp_rest")); ?>';
        
        $.ajax({
            url: apiUrl + 'groups/' + groupId + '/members/' + userId,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(response) {
                $btn.closest('tr').fadeOut(function() {
                    $(this).remove();
                });
            },
            error: function(error) {
                alert('Failed to remove member');
            }
        });
    });
});
</script>

