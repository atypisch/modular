<?php
/**
 * Group Edit Admin Page
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
$group = Simuze_Group::get($group_id);

if (!$group) {
    echo '<div class="notice notice-error"><p>Group not found.</p></div>';
    return;
}

// Handle form submission
if (isset($_POST['update_group'])) {
    set_time_limit(90);
    md_verify_csrf();

    $data = array(
        'name'        => sanitize_text_field($_POST['name']),
        'description' => strip_tags($_POST['description'], '<p><br><strong><em><ul><ol><li><a><h2><h3><h4><blockquote>'),
        'status'      => sanitize_text_field($_POST['status']),
        'city'        => sanitize_text_field($_POST['city']),
        'province'    => sanitize_text_field($_POST['province']),
        'country'     => sanitize_text_field($_POST['country']),
        'lat'         => isset($_POST['lat']) ? floatval($_POST['lat']) : null,
        'lng'         => isset($_POST['lng']) ? floatval($_POST['lng']) : null,
    );

    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = rtrim(md_asset_url(), '/') . '/../uploads/groups/';
        $upload_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/groups/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }
        $ext      = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = 'avatar-' . $group_id . '-' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path . $filename)) {
            $data['avatar_url'] = md_home_url('/uploads/groups/' . $filename);
        }
    }

    // Handle header upload
    if (isset($_FILES['header']) && $_FILES['header']['error'] === UPLOAD_ERR_OK) {
        $upload_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/groups/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }
        $ext      = pathinfo($_FILES['header']['name'], PATHINFO_EXTENSION);
        $filename = 'header-' . $group_id . '-' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['header']['tmp_name'], $upload_path . $filename)) {
            $data['header_url'] = md_home_url('/uploads/groups/' . $filename);
        }
    }

    // Update group type
    if (isset($_POST['group_type_id'])) {
        $data['group_type_id'] = intval($_POST['group_type_id']);
    }

    $result = $group->update($data, ['skip_location_groups' => true]);

    $edit_url = (function_exists('md_url') ? md_url('/admin/module-page.php') : (defined('ADMIN_URL') ? ADMIN_URL . '/module-page.php' : '')) . '?module=simuze-community&page=groups&action=edit&group_id=' . $group_id;
    if ($result) {
        $_SESSION['simuze_group_edit_message'] = 'success';
    } else {
        $_SESSION['simuze_group_edit_message'] = 'error';
    }
    header('Location: ' . $edit_url);
    exit;
}

// Get group type
$group_type_data = $group->get_group_type();

// Get all group types
$all_group_types = md_db_query("SELECT * FROM simuze_group_types WHERE deleted_at IS NULL ORDER BY name ASC");

// Get creator
$creator = Simuze_User::get($group->creator_id);

// Get members
$members_result = $group->get_members(array('per_page' => 100));
$members = $members_result['members'];

$admin_base   = md_url('/admin/module-page.php?module=simuze-community&page=');
$groups_url   = $admin_base . 'groups';
$users_url    = $admin_base . 'users';
$api_base_url = md_url('/api/simezu-ajax.php');
$csrf_token   = md_csrf_token();
?>

<div class="container module-page simuze-module" id="simuze-group-edit">
    <?php
    if (!empty($_SESSION['simuze_group_edit_message'])) {
        if ($_SESSION['simuze_group_edit_message'] === 'success') {
            echo '<div class="alert alert-success" style="margin: var(--spacing-md);">Group updated successfully!</div>';
        } else {
            echo '<div class="alert alert-error" style="margin: var(--spacing-md);">Failed to update group.</div>';
        }
        unset($_SESSION['simuze_group_edit_message']);
    }
    ?>
    <div class="page-header">
        <h1>Edit Group: <?php echo esc_html($group->name); ?></h1>
    </div>

    <form method="post" enctype="multipart/form-data" id="group-edit-form">
        <?php md_csrf_field(); ?>
        <input type="hidden" name="update_group" value="1">

        <div class="row" style="gap: 1.5rem; align-items: flex-start;">
            <div class="col" style="flex: 1; min-width: 0;">
                <!-- Basic Information -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header"><span>Basic Information</span></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Group Name</label>
                            <input type="text" id="name" name="name" value="<?php echo esc_attr($group->name); ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="10" class="form-control"><?php echo esc_textarea($group->description); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="group_type_id">Group Type</label>
                            <select id="group_type_id" name="group_type_id" class="form-control">
                                <option value="">— Select —</option>
                                <?php foreach ($all_group_types as $type): ?>
                                    <option value="<?php echo esc_attr($type['id']); ?>"
                                            <?php selected($group->group_type_id, $type['id']); ?>>
                                        <?php echo esc_html($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="public" <?php selected($group->status, 'public'); ?>>Public</option>
                                <option value="private" <?php selected($group->status, 'private'); ?>>Private</option>
                                <option value="hidden" <?php selected($group->status, 'hidden'); ?>>Hidden</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header"><span>Location</span></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?php echo esc_attr($group->city); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="province">Province</label>
                            <input type="text" id="province" name="province" value="<?php echo esc_attr($group->province); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" value="<?php echo esc_attr($group->country); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="lat">Latitude</label>
                            <input type="number" id="lat" name="lat" value="<?php echo esc_attr($group->lat); ?>" step="any" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="lng">Longitude</label>
                            <input type="number" id="lng" name="lng" value="<?php echo esc_attr($group->lng); ?>" step="any" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Members -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header"><span>Members (<?php echo count($members); ?>)</span></div>
                    <div class="card-body" style="overflow-x: auto;">
                        <?php if (!empty($members)): ?>
                            <table class="table">
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
                                                    <br><small class="text-muted">@<?php echo esc_html($member_user->username); ?></small>
                                                <?php else: ?>
                                                    User ID: <?php echo esc_html($member['user_id']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <select class="form-control member-role-select" data-user-id="<?php echo esc_attr($member['user_id']); ?>" data-group-id="<?php echo esc_attr($group->id); ?>">
                                                    <option value="member" <?php selected($member['role'], 'member'); ?>>Member</option>
                                                    <option value="mod" <?php selected($member['role'], 'mod'); ?>>Moderator</option>
                                                    <option value="admin" <?php selected($member['role'], 'admin'); ?>>Admin</option>
                                                </select>
                                            </td>
                                            <td><?php echo esc_html(date('Y-m-d', strtotime($member['joined_at']))); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-secondary remove-member-btn"
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
                            <p class="text-muted">No members yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col" style="flex: 0 0 320px; min-width: 0;">
                <!-- Images -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header"><span>Images</span></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="avatar">Avatar</label>
                            <?php if ($group->avatar_url): ?>
                                <img src="<?php echo esc_url($group->avatar_url); ?>" alt="Avatar" style="max-width: 150px; margin-bottom: 0.5rem; display: block;">
                            <?php endif; ?>
                            <input type="file" id="avatar" name="avatar" accept="image/*" class="form-control">
                            <p class="help-text text-muted" style="margin-top: 0.25rem;">Recommended: 200×200px</p>
                        </div>
                        <div class="form-group">
                            <label for="header">Header Image</label>
                            <?php if ($group->header_url): ?>
                                <img src="<?php echo esc_url($group->header_url); ?>" alt="Header" style="max-width: 100%; margin-bottom: 0.5rem; display: block;">
                            <?php endif; ?>
                            <input type="file" id="header" name="header" accept="image/*" class="form-control">
                            <p class="help-text text-muted" style="margin-top: 0.25rem;">Recommended: 1200×300px</p>
                        </div>
                    </div>
                </div>

                <!-- Group Information -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header"><span>Group Information</span></div>
                    <div class="card-body">
                        <table class="table">
                            <tr><th style="width: 40%;">Group ID</th><td><?php echo esc_html($group->id); ?></td></tr>
                            <tr><th>Slug</th><td><?php echo esc_html($group->slug); ?></td></tr>
                            <tr><th>Creator</th><td>
                                <?php if ($creator): ?>
                                    <a href="<?php echo esc_url($users_url . '&action=edit&user_id=' . $creator->id); ?>"><?php echo esc_html($creator->display_name); ?></a>
                                <?php else: ?>
                                    User ID: <?php echo esc_html($group->creator_id); ?>
                                <?php endif; ?>
                            </td></tr>
                            <tr><th>Created</th><td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($group->created_at))); ?></td></tr>
                            <tr><th>Updated</th><td><?php echo esc_html($group->updated_at ? date('Y-m-d H:i:s', strtotime($group->updated_at)) : '—'); ?></td></tr>
                            <tr><th>Member Count</th><td><?php echo esc_html($group->get_member_count()); ?></td></tr>
                        </table>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header"><span>Actions</span></div>
                    <div class="card-body">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Update Group</button>
                        </div>
                        <div class="form-group">
                            <a href="<?php echo esc_url($group->get_url()); ?>" class="btn btn-secondary" style="width: 100%;" target="_blank"><i class="fas fa-external-link-alt" aria-hidden="true"></i> View Group</a>
                        </div>
                        <div class="form-group">
                            <a href="<?php echo esc_url($groups_url); ?>" class="btn btn-secondary" style="width: 100%;"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Groups</a>
                        </div>
                        <div class="form-group">
                            <a href="<?php echo esc_url($groups_url . '&action=delete&group_id=' . $group->id . '&_csrf=' . urlencode(md_csrf_token())); ?>"
                               class="btn btn-secondary btn-icon-danger"
                               style="width: 100%;"
                               onclick="return confirm('Are you sure you want to delete this group? This action cannot be undone.');">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i> Delete Group
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(function() {
    var apiUrl = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.apiUrl)
        ? simuzeCommunity.apiUrl
        : '<?php echo esc_js(md_url('/api/simezu-ajax.php?action=simuze_rest&route=simuze/v1/')); ?>';
    var csrfToken = '<?php echo esc_js($csrf_token); ?>';

    document.querySelectorAll('.member-role-select').forEach(function(select) {
        select._originalValue = select.value;
        select.addEventListener('change', function() {
            var userId = this.getAttribute('data-user-id');
            var groupId = this.getAttribute('data-group-id');
            var newRole = this.value;
            var self = this;
            fetch(apiUrl + 'groups/' + groupId + '/update-member', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ user_id: parseInt(userId, 10), role: newRole })
            }).then(function(r) {
                if (r.ok) { self._originalValue = newRole; alert('Member role updated successfully!'); }
                else { throw new Error(); }
            }).catch(function() {
                alert('Failed to update member role');
                self.value = self._originalValue;
            });
        });
    });

    document.querySelectorAll('.remove-member-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to remove this member?')) return;
            var userId = this.getAttribute('data-user-id');
            var groupId = this.getAttribute('data-group-id');
            var row = this.closest('tr');
            fetch(apiUrl + 'groups/' + groupId + '/members/' + userId, {
                method: 'DELETE',
                headers: { 'X-CSRF-Token': csrfToken }
            }).then(function(r) {
                if (r.ok) row.remove();
                else alert('Failed to remove member');
            }).catch(function() { alert('Failed to remove member'); });
        });
    });
})();
</script>
