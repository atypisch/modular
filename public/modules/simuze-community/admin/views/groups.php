<?php
/**
 * Groups Admin Page
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

$base_url = md_url('/admin/module-page.php?module=simuze-community&page=groups');

// Handle quick-edit (single group: name, type, status)
if (isset($_POST['quick_edit_group']) && isset($_POST['group_id'])) {
    md_verify_csrf();
    $g = Simuze_Group::get((int) $_POST['group_id']);
    if ($g) {
        $data = array(
            'name'          => sanitize_text_field($_POST['name'] ?? $g->name),
            'group_type_id' => isset($_POST['group_type_id']) && $_POST['group_type_id'] !== '' ? (int) $_POST['group_type_id'] : $g->group_type_id,
            'status'        => in_array($_POST['status'] ?? '', array('public', 'private', 'hidden'), true) ? $_POST['status'] : $g->status,
        );
        $g->update($data, array('skip_location_groups' => true));
    }
    header('Location: ' . $base_url . '&message=quick_updated');
    exit;
}

// Handle bulk actions
if (isset($_POST['action']) && isset($_POST['group_ids']) && is_array($_POST['group_ids'])) {
    md_verify_csrf();

    $action    = sanitize_text_field($_POST['action']);
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
                case 'change_type':
                    $type_id = isset($_POST['group_type_id']) ? (int) $_POST['group_type_id'] : 0;
                    if ($type_id > 0) {
                        $group->update(array('group_type_id' => $type_id), array('skip_location_groups' => true));
                    }
                    break;
            }
        }
    }

    header('Location: ' . $base_url . '&message=updated');
    exit;
}

// Handle single delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['group_id'])) {
    if (!isset($_GET['_csrf']) || $_GET['_csrf'] !== md_csrf_token()) {
        header('Location: ' . $base_url . '&message=csrf_error');
        exit;
    }

    $group = Simuze_Group::get(intval($_GET['group_id']));
    if ($group) {
        $group->delete();
        header('Location: ' . $base_url . '&message=deleted');
        exit;
    }
}

// Get filter parameters
$status     = isset($_GET['status'])     ? sanitize_text_field($_GET['status'])     : 'all';
$group_type = isset($_GET['group_type']) ? sanitize_text_field($_GET['group_type']) : '';
$search     = isset($_GET['s'])          ? sanitize_text_field($_GET['s'])          : '';
$paged      = isset($_GET['paged'])      ? max(1, intval($_GET['paged']))            : 1;
$per_page   = 20;

$args = array(
    'per_page' => $per_page,
    'page'     => $paged,
    'search'   => $search,
);

if ($status !== 'all') {
    $args['status'] = $status;
}
if ($group_type) {
    $args['group_type'] = $group_type;
}

$result      = Simuze_Group::get_all($args);
$groups      = $result['groups'];
$total       = $result['total'];
$total_pages = (int) ceil($total / $per_page);

// Get all group types for filter
$all_group_types = md_db_query(
    "SELECT * FROM simuze_group_types WHERE deleted_at IS NULL ORDER BY name ASC"
);

// Message
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'updated':
            $message = '<div class="alert alert-success" style="margin: var(--spacing-md);">Groups updated successfully!</div>';
            break;
        case 'deleted':
            $message = '<div class="alert alert-success" style="margin: var(--spacing-md);">Group deleted successfully!</div>';
            break;
        case 'quick_updated':
            $message = '<div class="alert alert-success" style="margin: var(--spacing-md);">Group updated successfully!</div>';
            break;
    }
}
?>

<div class="container module-page simuze-module" id="simuze-groups-list">
    <div class="page-header">
        <h1>Groups</h1>
    </div>

    <?php echo $message; ?>

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-body">
            <form method="get" action="" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                <input type="hidden" name="module" value="simuze-community">
                <input type="hidden" name="page" value="groups">
                <select name="status" class="form-control" style="width: auto;">
                    <option value="all"     <?php selected($status, 'all'); ?>>All Status</option>
                    <option value="public"  <?php selected($status, 'public'); ?>>Public</option>
                    <option value="private" <?php selected($status, 'private'); ?>>Private</option>
                    <option value="hidden"  <?php selected($status, 'hidden'); ?>>Hidden</option>
                </select>
                <select name="group_type" class="form-control" style="width: auto;">
                    <option value="">All Types</option>
                    <?php foreach ($all_group_types as $type): ?>
                        <option value="<?php echo esc_attr($type['slug']); ?>" <?php selected($group_type, $type['slug']); ?>>
                            <?php echo esc_html($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="s" placeholder="Search groups..." value="<?php echo esc_attr($search); ?>" class="form-control" style="width: 200px;">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>

    <form method="post" id="groups-form">
        <?php md_csrf_field(); ?>

        <div class="form-group" style="margin-bottom: 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
            <select name="action" id="bulk-action-select" class="form-control" style="width: auto;">
                <option value="">Bulk Actions</option>
                <option value="delete">Delete</option>
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate</option>
                <option value="change_type">Change group type</option>
            </select>
            <span id="bulk-change-type-wrap" style="display: none;">
                <label for="bulk-group-type-id" style="margin: 0 0.25rem 0 0;">Set type to</label>
                <select name="group_type_id" id="bulk-group-type-id" class="form-control" style="width: auto;">
                    <option value="">— Select type —</option>
                    <?php foreach ($all_group_types as $t): ?>
                        <option value="<?php echo esc_attr($t['id']); ?>"><?php echo esc_html($t['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
            <button type="submit" class="btn btn-secondary">Apply</button>
        </div>

        <div class="card simuze-groups-table-card">
            <div class="card-body simuze-groups-table-wrap">
                <table class="table simuze-groups-table">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" id="cb-select-all" aria-label="Select all"></th>
                            <th class="col-id">ID</th>
                            <th class="col-avatar">Avatar</th>
                            <th class="col-name">Name</th>
                            <th class="col-type">Type</th>
                            <th class="col-members">Members</th>
                            <th class="col-location">Location</th>
                            <th class="col-status">Status</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($groups)): ?>
                            <?php foreach ($groups as $group): ?>
                                <?php
                                $group_type_data = $group->get_group_type();
                                $member_count    = $group->get_member_count();
                                $edit_url        = $base_url . '&action=edit&group_id=' . $group->id;
                                $delete_url      = $base_url . '&action=delete&group_id=' . $group->id . '&_csrf=' . urlencode(md_csrf_token());
                                ?>
                                <tr>
                                    <td class="col-check"><input type="checkbox" name="group_ids[]" value="<?php echo esc_attr($group->id); ?>"></td>
                                    <td class="col-id"><?php echo esc_html($group->id); ?></td>
                                    <td class="col-avatar">
                                        <?php if ($group->avatar_url): ?>
                                            <img src="<?php echo esc_url($group->avatar_url); ?>" alt="" class="simuze-group-avatar">
                                        <?php else: ?>
                                            <div class="simuze-group-avatar simuze-group-avatar-placeholder"><?php echo esc_html(mb_substr($group->name, 0, 1)); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-name" title="<?php echo esc_attr($group->name . ($group->description ? ' – ' . mb_strimwidth($group->description, 0, 80, '…') : '')); ?>">
                                        <strong><a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($group->name); ?></a></strong>
                                    </td>
                                    <td class="col-type">
                                        <?php if ($group_type_data): ?>
                                            <span class="badge badge-secondary"><?php echo esc_html($group_type_data['name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-members"><?php echo esc_html($member_count); ?></td>
                                    <td class="col-location">
                                        <?php if ($group->city): ?>
                                            <?php echo esc_html($group->city); ?>
                                            <?php if ($group->country): ?>, <?php echo esc_html($group->country); ?><?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-status">
                                        <span class="badge status-<?php echo esc_attr($group->status); ?>">
                                            <?php echo esc_html(ucfirst($group->status)); ?>
                                        </span>
                                    </td>
                                    <td class="col-actions">
                                        <button type="button" class="btn btn-sm btn-icon quick-edit-btn" title="Quick edit"
                                                data-group-id="<?php echo esc_attr($group->id); ?>"
                                                data-name="<?php echo esc_attr($group->name); ?>"
                                                data-type-id="<?php echo esc_attr($group->group_type_id); ?>"
                                                data-status="<?php echo esc_attr($group->status); ?>"><i class="fas fa-bolt" aria-hidden="true"></i></button>
                                        <a href="<?php echo esc_url($edit_url); ?>" class="btn btn-sm btn-icon" title="Edit"><i class="fas fa-pen" aria-hidden="true"></i></a>
                                        <a href="<?php echo esc_url($group->get_url()); ?>" class="btn btn-sm btn-icon" title="View" target="_blank"><i class="fas fa-external-link-alt" aria-hidden="true"></i></a>
                                        <a href="<?php echo esc_url($delete_url); ?>" class="btn btn-sm btn-icon btn-icon-danger" title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this group?');"><i class="fas fa-trash-alt" aria-hidden="true"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-muted">No groups found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <?php if ($total_pages > 1): ?>
        <div style="margin-top: 1rem; display: flex; gap: 0.25rem; flex-wrap: wrap;">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="<?php echo esc_url($base_url . '&paged=' . $p . ($status !== 'all' ? '&status=' . urlencode($status) : '') . ($group_type ? '&group_type=' . urlencode($group_type) : '') . ($search ? '&s=' . urlencode($search) : '')); ?>"
                   class="btn btn-sm <?php echo $p === $paged ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <!-- Quick edit modal -->
    <div id="quick-edit-modal" class="modular-modal-overlay" style="display: none;" aria-hidden="true">
        <div class="modular-modal-dialog">
            <div class="modular-modal-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 1rem;">Quick edit group</h2>
                <button type="button" class="btn btn-sm btn-secondary" id="quick-edit-modal-close" aria-label="Close">&times;</button>
            </div>
            <form method="post" id="quick-edit-form">
                <?php md_csrf_field(); ?>
                <input type="hidden" name="quick_edit_group" value="1">
                <input type="hidden" name="group_id" id="quick-edit-group-id" value="">
                <div class="modular-modal-body">
                    <div class="form-group">
                        <label for="quick-edit-name">Name</label>
                        <input type="text" id="quick-edit-name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="quick-edit-type">Group type</label>
                        <select id="quick-edit-type" name="group_type_id" class="form-control">
                            <option value="">— Select —</option>
                            <?php foreach ($all_group_types as $t): ?>
                                <option value="<?php echo esc_attr($t['id']); ?>"><?php echo esc_html($t['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quick-edit-status">Status</label>
                        <select id="quick-edit-status" name="status" class="form-control">
                            <option value="public">Public</option>
                            <option value="private">Private</option>
                            <option value="hidden">Hidden</option>
                        </select>
                    </div>
                </div>
                <div class="modular-modal-footer">
                    <button type="button" class="btn btn-secondary" id="quick-edit-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.badge { display:inline-block; padding:0.2rem 0.5rem; border-radius:3px; font-size:0.75rem; font-weight:600; }
.badge-secondary { background:var(--neutral-200,#eee); color:var(--neutral-700,#333); }
.status-public { background:var(--success-bg,#d4edda); color:var(--success-text,#155724); }
.status-private { background:var(--warning-bg,#fff3cd); color:var(--warning-text,#856404); }
.status-hidden { background:var(--error-bg,#f8d7da); color:var(--error-text,#721c24); }
/* Groups table: max 100% width, never overflow */
.simuze-groups-table-card { max-width: 100%; overflow: hidden; }
.simuze-groups-table-wrap { padding: 0 !important; max-width: 100%; overflow-x: auto; }
.simuze-groups-table { table-layout: fixed; width: 100%; max-width: 100%; }
.simuze-groups-table .col-check { width: 3.5%; padding: 0.35rem 0.25rem; text-align: center; }
.simuze-groups-table .col-id { width: 3%; padding: 0.35rem 0.25rem; font-size: 0.85rem; white-space: nowrap; }
.simuze-groups-table .col-avatar { width: 6%; padding: 0.35rem 0.25rem; vertical-align: middle; }
.simuze-groups-table .col-name { width: 20%; max-width: 20%; padding: 0.35rem 0.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.simuze-groups-table .col-name a { display: block; overflow: hidden; text-overflow: ellipsis; }
.simuze-groups-table .col-type { width: 11%; padding: 0.35rem 0.5rem; }
.simuze-groups-table .col-members { width: 10%; padding: 0.35rem 0.5rem; text-align: center; }
.simuze-groups-table .col-location { width: 28%; padding: 0.35rem 0.5rem; font-size: 0.9rem; }
.simuze-groups-table .col-status { width: 8%; padding: 0.35rem 0.5rem; }
.simuze-groups-table .col-actions { width: 10.5%; padding: 0.35rem 0.25rem; white-space: nowrap; }
.simuze-group-avatar { width: 32px; height: 32px; object-fit: cover; border-radius: 4px; display: block; }
.simuze-group-avatar-placeholder { width: 32px; height: 32px; background: var(--neutral-200,#ddd); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 600; }
.btn-icon { min-width: 2rem; padding: 0.25rem 0.4rem; }
.btn-icon .fas { margin: 0; }
.btn-icon-danger { color: var(--error-text, #c00) !important; }
</style>

<script>
(function() {
    var cb = document.getElementById('cb-select-all');
    if (cb) cb.addEventListener('change', function() {
        document.querySelectorAll('input[name="group_ids[]"]').forEach(function(c) { c.checked = this.checked; }.bind(this));
    });

    var bulkSelect = document.getElementById('bulk-action-select');
    var bulkTypeWrap = document.getElementById('bulk-change-type-wrap');
    if (bulkSelect && bulkTypeWrap) {
        bulkSelect.addEventListener('change', function() {
            bulkTypeWrap.style.display = this.value === 'change_type' ? 'inline' : 'none';
        });
    }

    var modal = document.getElementById('quick-edit-modal');
    var form = document.getElementById('quick-edit-form');
    function closeQuickEdit() {
        if (modal) { modal.style.display = 'none'; modal.setAttribute('aria-hidden', 'true'); }
    }
    document.querySelectorAll('.quick-edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-group-id');
            var name = this.getAttribute('data-name');
            var typeId = this.getAttribute('data-type-id');
            var status = this.getAttribute('data-status');
            document.getElementById('quick-edit-group-id').value = id;
            document.getElementById('quick-edit-name').value = name || '';
            document.getElementById('quick-edit-type').value = typeId || '';
            document.getElementById('quick-edit-status').value = status || 'public';
            if (modal) { modal.style.display = 'flex'; modal.setAttribute('aria-hidden', 'false'); }
        });
    });
    if (document.getElementById('quick-edit-modal-close')) document.getElementById('quick-edit-modal-close').addEventListener('click', closeQuickEdit);
    if (document.getElementById('quick-edit-cancel')) document.getElementById('quick-edit-cancel').addEventListener('click', closeQuickEdit);
    if (modal) modal.addEventListener('click', function(e) { if (e.target === modal) closeQuickEdit(); });
})();
</script>
