<?php
/**
 * Groups Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle bulk actions
if (isset($_POST['action']) && isset($_POST['group_ids']) && is_array($_POST['group_ids'])) {
    check_admin_referer('bulk-groups');
    
    $action = sanitize_text_field($_POST['action']);
    $group_ids = array_map('intval', $_POST['group_ids']);
    
    foreach ($group_ids as $group_id) {
        $group = Simuze_Group::get($group_id);
        if ($group) {
            switch ($action) {
                case 'delete':
                    $group->delete();
                    break;
                case 'activate':
                    $group->update(array('status' => 'public'));
                    break;
                case 'deactivate':
                    $group->update(array('status' => 'private'));
                    break;
            }
        }
    }
    
    wp_redirect(add_query_arg(array('page' => 'simuze-groups', 'message' => 'updated'), admin_url('admin.php')));
    exit;
}

// Handle single delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['group_id'])) {
    check_admin_referer('delete-group_' . $_GET['group_id']);
    
    $group = Simuze_Group::get(intval($_GET['group_id']));
    if ($group) {
        $group->delete();
        wp_redirect(add_query_arg(array('page' => 'simuze-groups', 'message' => 'deleted'), admin_url('admin.php')));
        exit;
    }
}

// Get filter parameters
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
$group_type = isset($_GET['group_type']) ? sanitize_text_field($_GET['group_type']) : '';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;

// Build query args
$args = array(
    'per_page' => $per_page,
    'page' => $paged,
    'search' => $search,
);

if ($status !== 'all') {
    $args['status'] = $status;
}

if ($group_type) {
    $args['group_type'] = $group_type;
}

$result = Simuze_Group::get_all($args);
$groups = $result['groups'];
$total = $result['total'];
$total_pages = ceil($total / $per_page);

// Get all group types for filter
global $wpdb;
$types_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_types';
$all_group_types = $wpdb->get_results("SELECT * FROM $types_table WHERE deleted_at IS NULL ORDER BY name ASC", ARRAY_A);

// Show message
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'updated':
            $message = '<div class="notice notice-success is-dismissible"><p>Groups updated successfully!</p></div>';
            break;
        case 'deleted':
            $message = '<div class="notice notice-success is-dismissible"><p>Group deleted successfully!</p></div>';
            break;
    }
}
?>

<div class="wrap">
    <h1>Groups</h1>
    
    <?php echo $message; ?>
    
    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="page" value="simuze-groups">
                <select name="status">
                    <option value="all" <?php selected($status, 'all'); ?>>All Status</option>
                    <option value="public" <?php selected($status, 'public'); ?>>Public</option>
                    <option value="private" <?php selected($status, 'private'); ?>>Private</option>
                    <option value="hidden" <?php selected($status, 'hidden'); ?>>Hidden</option>
                </select>
                <select name="group_type">
                    <option value="">All Types</option>
                    <?php foreach ($all_group_types as $type): ?>
                        <option value="<?php echo esc_attr($type['slug']); ?>" <?php selected($group_type, $type['slug']); ?>>
                            <?php echo esc_html($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="s" placeholder="Search groups..." value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="button">Filter</button>
            </form>
        </div>
    </div>
    
    <form method="post" id="groups-form">
        <?php wp_nonce_field('bulk-groups'); ?>
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="action">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                </select>
                <button type="submit" class="button action">Apply</button>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </td>
                    <th>ID</th>
                    <th>Avatar</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Creator</th>
                    <th>Members</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($groups)): ?>
                    <?php foreach ($groups as $group): ?>
                        <?php
                        $group_type_data = $group->get_group_type();
                        $creator = Simuze_User::get($group->creator_id);
                        $member_count = $group->get_member_count();
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="group_ids[]" value="<?php echo esc_attr($group->id); ?>">
                            </th>
                            <td><?php echo esc_html($group->id); ?></td>
                            <td>
                                <?php if ($group->avatar_url): ?>
                                    <img src="<?php echo esc_url($group->avatar_url); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; background: #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                        <span style="font-size: 20px;"><?php echo esc_html(substr($group->name, 0, 1)); ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=simuze-groups&action=edit&group_id=' . $group->id); ?>">
                                        <?php echo esc_html($group->name); ?>
                                    </a>
                                </strong>
                                <?php if ($group->description): ?>
                                    <br><small style="color: #666;"><?php echo esc_html(wp_trim_words($group->description, 10)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($group_type_data): ?>
                                    <span class="group-type-badge"><?php echo esc_html($group_type_data['name']); ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($creator): ?>
                                    <a href="<?php echo admin_url('admin.php?page=simuze-users&action=edit&user_id=' . $creator->id); ?>">
                                        <?php echo esc_html($creator->display_name); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($member_count); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($group->status); ?>">
                                    <?php echo esc_html(ucfirst($group->status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($group->city): ?>
                                    <?php echo esc_html($group->city); ?>
                                    <?php if ($group->country): ?>
                                        , <?php echo esc_html($group->country); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($group->created_at))); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=simuze-groups&action=edit&group_id=' . $group->id); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo esc_url($group->get_url()); ?>" class="button button-small" target="_blank">View</a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=simuze-groups&action=delete&group_id=' . $group->id), 'delete-group_' . $group->id); ?>" 
                                   class="button button-small delete-link" 
                                   onclick="return confirm('Are you sure you want to delete this group?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11">No groups found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged,
                ));
                echo $page_links;
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}
.status-public {
    background: #d4edda;
    color: #155724;
}
.status-private {
    background: #fff3cd;
    color: #856404;
}
.status-hidden {
    background: #f8d7da;
    color: #721c24;
}
.group-type-badge {
    display: inline-block;
    padding: 2px 6px;
    background: #f0f0f0;
    border-radius: 3px;
    font-size: 11px;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#cb-select-all').on('change', function() {
        $('input[name="group_ids[]"]').prop('checked', this.checked);
    });
});
</script>











