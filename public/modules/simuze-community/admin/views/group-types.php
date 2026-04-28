<?php
/**
 * Group Types Admin Page
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

 $table = 'simuze_group_types';

// Check if table exists
$table_exists = false;
try {
    // Use direct LIKE literal to avoid driver quirks with prepared SHOW statements
    $table_check = md_db_var("SHOW TABLES LIKE 'simuze_group_types'");
    $table_exists = ($table_check === $table);
} catch (\Exception $e) {
    $table_exists = false;
}

// If table is missing (e.g. after manual schema import), attempt to create it
if (!$table_exists) {
    try {
        $pdo = md_db();
        if ($pdo instanceof \PDO) {
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
        // Assume table now exists; subsequent queries will surface errors if not
        $table_exists = true;
    } catch (\Throwable $e) {
        $table_exists = false;
    }
}

// If table doesn't exist, show error and return early
// BUT only if we're not handling a POST request (which might need to redirect)
if (!$table_exists && !isset($_POST['action'])) {
    ?>
    <div class="container module-page simuze-module">
        <div class="page-header"><h1>Group Types</h1></div>
        <div class="alert alert-error">Group types table does not exist. Please deactivate and reactivate the Simuze Community module to create the tables.</div>
    </div>
    <?php
    return;
}

// Initialize message variables
$message = '';
$message_type = '';

// Admin URL helper for this page
$admin_page_url = md_url('/admin/module-page.php?module=simuze-community&page=group-types');

// Handle form submissions FIRST - must be before any output
if (isset($_POST['action']) && isset($_POST['_csrf_token']) && md_verify_csrf()) {
    if ($_POST['action'] === 'add' && isset($_POST['name']) && isset($_POST['slug'])) {
        $name = trim(strip_tags($_POST['name']));
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', trim($_POST['slug']))));
        $icon = !empty($_POST['icon']) ? trim(strip_tags($_POST['icon'])) : null;

        // Check if slug already exists
        $exists = md_db_var("SELECT id FROM $table WHERE slug = ?", [$slug]);

        if ($exists) {
            $message = 'A group type with this slug already exists.';
            $message_type = 'error';
        } else {
            $inserted = md_db_insert($table, [
                'name'       => $name,
                'slug'       => $slug,
                'icon'       => $icon,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($inserted) {
                $redirect_url = $admin_page_url . '&message=added';
                header('Location: ' . $redirect_url);
                exit;
            } else {
                $message = 'Failed to add group type.';
                $message_type = 'error';
            }
        }
    } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
        $edit_slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', trim($_POST['slug']))));

        // Check if slug already exists for another group type
        $existing = md_db_var("SELECT id FROM $table WHERE slug = ? AND id != ?", [$edit_slug, (int) $_POST['id']]);

        if ($existing) {
            $message = 'A group type with this slug already exists.';
            $message_type = 'error';
        } else {
            $updated = md_db_update($table, [
                'name'       => trim(strip_tags($_POST['name'])),
                'slug'       => $edit_slug,
                'icon'       => !empty($_POST['icon']) ? trim(strip_tags($_POST['icon'])) : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => (int) $_POST['id']]);

            if ($updated !== false) {
                $redirect_url = $admin_page_url . '&message=updated';
                header('Location: ' . $redirect_url);
                exit;
            } else {
                $message = 'Failed to update group type.';
                $message_type = 'error';
            }
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $delete_id = (int) $_POST['id'];

        $exists = md_db_var("SELECT id FROM $table WHERE id = ?", [$delete_id]);

        if (!$exists) {
            $message = 'Group type not found.';
            $message_type = 'error';
        } else {
            // Check if deleted_at column exists
            $cols_rows = md_db_query("DESCRIBE $table");
            $col_names = array_column($cols_rows, 'Field');
            if (in_array('deleted_at', $col_names)) {
                $result = md_db_update($table, ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $delete_id]);
            } else {
                $result = md_db()->prepare("DELETE FROM $table WHERE id = ?")->execute([$delete_id]);
            }

            if ($result !== false) {
                $redirect_url = $admin_page_url . '&message=deleted';
                header('Location: ' . $redirect_url);
                exit;
            } else {
                $message = 'Failed to delete group type.';
                $message_type = 'error';
            }
        }
    }
}

// Show success message from redirect
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'added':
            $message = 'Group type added successfully!';
            $message_type = 'success';
            break;
        case 'updated':
            $message = 'Group type updated successfully!';
            $message_type = 'success';
            break;
        case 'deleted':
            $message = 'Group type deleted successfully!';
            $message_type = 'success';
            break;
    }
}

// Get group types - check if deleted_at column exists
$group_types = array();

if ($table_exists) {
    $cols_rows    = md_db_query("DESCRIBE $table");
    $col_names    = array_column($cols_rows, 'Field');
    $where_clause = in_array('deleted_at', $col_names) ? "WHERE deleted_at IS NULL" : "";
    $group_types  = md_db_query("SELECT * FROM $table $where_clause ORDER BY name ASC");
    if (!is_array($group_types)) {
        $group_types = array();
    }
}
?>

<div class="container module-page simuze-module">
    <div class="page-header">
        <h1>Group Types</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo esc_attr($message_type === 'error' ? 'error' : 'success'); ?>" style="margin-bottom: 1.5rem;">
            <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>

    <!-- Form: boven, ~50% breedte -->
    <div class="card" style="margin-bottom: 1.5rem; max-width: 50%; width: 100%;">
        <div class="card-header">
            <span id="form-title">Add New Group Type</span>
        </div>
        <div class="card-body">
            <form method="post" id="group-type-form">
                <?php echo md_csrf_field(); ?>
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="id" id="form-id" value="">

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" class="form-control" required>
                    <p class="help-text text-muted">Lowercase, URL-friendly identifier (e.g. "artist").</p>
                </div>

                <div class="form-group">
                    <label for="icon">Icon (optional)</label>
                    <input type="text" id="icon" name="icon" class="form-control" placeholder="dashicons-admin-users">
                    <p class="help-text text-muted">PrimeIcons or Dashicons name, e.g. "pi pi-users" or "dashicons-admin-users".</p>
                </div>

                <div class="form-group" style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" id="submit-button">Add Group Type</button>
                    <button type="button" class="btn btn-secondary" id="cancel-edit" style="display: none;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel in card, 100% breedte -->
    <div class="card" style="width: 100%; overflow-x: auto;">
        <div class="card-header">
            <span>Existing Group Types</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Icon</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($group_types)): ?>
                        <?php foreach ($group_types as $type): ?>
                            <tr>
                                <td><?php echo esc_html($type['id']); ?></td>
                                <td><?php echo esc_html($type['name']); ?></td>
                                <td><?php echo esc_html($type['slug']); ?></td>
                                <td><?php echo esc_html($type['icon'] ?? '—'); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-icon edit-group-type" title="Edit" data-id="<?php echo esc_attr($type['id']); ?>" data-name="<?php echo esc_attr($type['name']); ?>" data-slug="<?php echo esc_attr($type['slug']); ?>" data-icon="<?php echo esc_attr($type['icon'] ?? ''); ?>"><i class="fas fa-pen" aria-hidden="true"></i></a>
                                    <a href="#" class="btn btn-sm btn-icon btn-icon-danger delete-group-type" title="Delete" data-id="<?php echo esc_attr($type['id']); ?>"><i class="fas fa-trash-alt" aria-hidden="true"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-muted">No group types found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    var formTitle = document.getElementById('form-title');
    var formAction = document.getElementById('form-action');
    var formId = document.getElementById('form-id');
    var nameInput = document.getElementById('name');
    var slugInput = document.getElementById('slug');
    var iconInput = document.getElementById('icon');
    var submitBtn = document.getElementById('submit-button');
    var cancelBtn = document.getElementById('cancel-edit');
    var formEl = document.getElementById('group-type-form');
    var csrfToken = '<?php echo htmlspecialchars(md_csrf_token(), ENT_QUOTES); ?>';

    function resetForm() {
        formId.value = '';
        formAction.value = 'add';
        nameInput.value = '';
        slugInput.value = '';
        iconInput.value = '';
        if (formTitle) formTitle.textContent = 'Add New Group Type';
        if (submitBtn) submitBtn.textContent = 'Add Group Type';
        if (cancelBtn) cancelBtn.style.display = 'none';
    }

    document.querySelectorAll('.edit-group-type').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            formId.value = this.getAttribute('data-id');
            formAction.value = 'edit';
            nameInput.value = this.getAttribute('data-name') || '';
            slugInput.value = this.getAttribute('data-slug') || '';
            iconInput.value = this.getAttribute('data-icon') || '';
            if (formTitle) formTitle.textContent = 'Edit Group Type';
            if (submitBtn) submitBtn.textContent = 'Update Group Type';
            if (cancelBtn) cancelBtn.style.display = 'inline-block';
            formEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    if (cancelBtn) cancelBtn.addEventListener('click', resetForm);

    document.querySelectorAll('.delete-group-type').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this group type?')) {
                var form = document.createElement('form');
                form.method = 'post';
                form.action = '';
                var fields = [
                    { name: 'action', value: 'delete' },
                    { name: 'id', value: this.getAttribute('data-id') },
                    { name: '_csrf_token', value: csrfToken }
                ];
                fields.forEach(function(f) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = f.name;
                    input.value = f.value;
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
})();
</script>
<style>
.btn-icon { min-width: 2rem; padding: 0.25rem 0.4rem; }
.btn-icon .fas { margin: 0; }
.btn-icon-danger { color: var(--error-text, #c00) !important; }
</style>

