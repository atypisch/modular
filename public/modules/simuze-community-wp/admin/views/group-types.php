<?php
/**
 * Group Types Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_types';

// Check if table exists - use simpler method
$table_exists = false;
$table_check = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
if ($table_check === $table) {
    $table_exists = true;
}

// If table doesn't exist, show error and return early
// BUT only if we're not handling a POST request (which might need to redirect)
if (!$table_exists && !isset($_POST['action'])) {
    ?>
    <div class="wrap">
        <h1>Group Types</h1>
        <div class="notice notice-error">
            <p>Group types table does not exist. Please deactivate and reactivate the Simuze Community plugin to create the tables.</p>
        </div>
    </div>
    <?php
    return;
}

// Initialize message variables
$message = '';
$message_type = '';

// Handle form submissions FIRST - must be before any output
// This prevents "headers already sent" errors
if (isset($_POST['action']) && isset($_POST['simuze_group_types_nonce']) && check_admin_referer('simuze_group_types_action', 'simuze_group_types_nonce')) {
    if ($_POST['action'] === 'add' && isset($_POST['name']) && isset($_POST['slug'])) {
        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_title($_POST['slug']);
        $icon = isset($_POST['icon']) && !empty($_POST['icon']) ? sanitize_text_field($_POST['icon']) : null;
        
        // Check if slug already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE slug = %s",
            $slug
        ));
        
        if ($exists) {
            $message = 'A group type with this slug already exists.';
            $message_type = 'error';
        } else {
            $result = $wpdb->insert(
                $table,
                array(
                    'name' => $name,
                    'slug' => $slug,
                    'icon' => $icon,
                    'created_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%s', '%s')
            );
            
            if ($result !== false) {
                // Redirect to avoid resubmission - must be before any output
                $redirect_url = add_query_arg(array('page' => 'simuze-group-types', 'message' => 'added'), admin_url('admin.php'));
                if (!headers_sent()) {
                    wp_safe_redirect($redirect_url);
                    exit;
                } else {
                    // Fallback if headers already sent
                    echo '<script>window.location.href = "' . esc_js($redirect_url) . '";</script>';
                    exit;
                }
            } else {
                $message = 'Failed to add group type. Error: ' . ($wpdb->last_error ?: 'Unknown error');
                $message_type = 'error';
            }
        }
    } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
        // Check if slug already exists for another group type
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE slug = %s AND id != %d",
            sanitize_title($_POST['slug']),
            (int) $_POST['id']
        ));
        
        if ($existing) {
            $message = 'A group type with this slug already exists.';
            $message_type = 'error';
        } else {
            $result = $wpdb->update(
                $table,
                array(
                    'name' => sanitize_text_field($_POST['name']),
                    'slug' => sanitize_title($_POST['slug']),
                    'icon' => isset($_POST['icon']) && !empty($_POST['icon']) ? sanitize_text_field($_POST['icon']) : null,
                    'updated_at' => current_time('mysql'),
                ),
                array('id' => (int) $_POST['id']),
                array('%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            // $result can be 0 if no rows changed, or false on error, or number of rows updated
            if ($result !== false) {
                // Redirect to avoid resubmission - must be before any output
                $redirect_url = add_query_arg(array('page' => 'simuze-group-types', 'message' => 'updated'), admin_url('admin.php'));
                if (!headers_sent()) {
                    wp_safe_redirect($redirect_url);
                    exit;
                } else {
                    // Fallback if headers already sent
                    echo '<script>window.location.href = "' . esc_js($redirect_url) . '";</script>';
                    exit;
                }
            } else {
                $message = 'Failed to update group type. Error: ' . ($wpdb->last_error ?: 'Unknown error');
                $message_type = 'error';
            }
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $delete_id = (int) $_POST['id'];
        
        // Check if group type exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE id = %d",
            $delete_id
        ));
        
        if (!$exists) {
            $message = 'Group type not found.';
            $message_type = 'error';
        } else {
            // Check if deleted_at column exists, if not use hard delete
            $columns = $wpdb->get_col("DESCRIBE $table");
            if (is_array($columns) && in_array('deleted_at', $columns)) {
                $result = $wpdb->update(
                    $table,
                    array('deleted_at' => current_time('mysql')),
                    array('id' => $delete_id),
                    array('%s'),
                    array('%d')
                );
            } else {
                $result = $wpdb->delete(
                    $table,
                    array('id' => $delete_id),
                    array('%d')
                );
            }
            
            if ($result !== false) {
                // Redirect to avoid resubmission - must be before any output
                $redirect_url = add_query_arg(array('page' => 'simuze-group-types', 'message' => 'deleted'), admin_url('admin.php'));
                if (!headers_sent()) {
                    wp_safe_redirect($redirect_url);
                    exit;
                } else {
                    // Fallback if headers already sent
                    echo '<script>window.location.href = "' . esc_js($redirect_url) . '";</script>';
                    exit;
                }
            } else {
                $message = 'Failed to delete group type. Error: ' . ($wpdb->last_error ?: 'Unknown error');
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

// Check if table exists before querying
if ($table_exists) {
    // Get columns to check for deleted_at
    $columns = $wpdb->get_col("DESCRIBE $table");
    if (is_array($columns) && in_array('deleted_at', $columns)) {
        $where_clause = "WHERE deleted_at IS NULL";
    } else {
        $where_clause = "";
    }
    
    // Build and execute query
    $query = "SELECT * FROM $table $where_clause ORDER BY name ASC";
    $group_types = $wpdb->get_results($query, ARRAY_A);
    
    // Log errors if any
    if ($wpdb->last_error) {
        error_log('Simuze Group Types Query Error: ' . $wpdb->last_error);
        error_log('Simuze Group Types Query: ' . $query);
    }
    
    // Ensure group_types is an array
    if (!is_array($group_types)) {
        $group_types = array();
    }
}
?>

<div class="wrap">
    <h1>Group Types</h1>
    
    <?php if ($message): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <h2 id="form-title">Add New Group Type</h2>
    <form method="post" id="group-type-form" style="margin-bottom: 30px;">
        <?php wp_nonce_field('simuze_group_types_action', 'simuze_group_types_nonce'); ?>
        <input type="hidden" name="action" id="form-action" value="add">
        <input type="hidden" name="id" id="form-id" value="">
        <table class="form-table">
            <tr>
                <th><label for="name">Name</label></th>
                <td><input type="text" id="name" name="name" required></td>
            </tr>
            <tr>
                <th><label for="slug">Slug</label></th>
                <td><input type="text" id="slug" name="slug" required></td>
            </tr>
            <tr>
                <th><label for="icon">Icon (optional)</label></th>
                <td><input type="text" id="icon" name="icon" placeholder="dashicons-admin-users"></td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" class="button button-primary" id="submit-button">Add Group Type</button>
            <button type="button" class="button" id="cancel-edit" style="display: none;">Cancel</button>
        </p>
    </form>
    
    <h2>Existing Group Types</h2>
    <table class="wp-list-table widefat fixed striped">
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
                        <td><?php echo esc_html($type['icon']); ?></td>
                        <td>
                            <a href="#" class="edit-group-type" data-id="<?php echo esc_attr($type['id']); ?>" data-name="<?php echo esc_attr($type['name']); ?>" data-slug="<?php echo esc_attr($type['slug']); ?>" data-icon="<?php echo esc_attr($type['icon']); ?>">Edit</a> |
                            <a href="#" class="delete-group-type" data-id="<?php echo esc_attr($type['id']); ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No group types found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    // Edit functionality
    $('.edit-group-type').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name');
        var slug = $(this).data('slug');
        var icon = $(this).data('icon') || '';
        
        // Populate form with existing values
        $('#form-id').val(id);
        $('#form-action').val('edit');
        $('#name').val(name);
        $('#slug').val(slug);
        $('#icon').val(icon);
        
        // Update form title and button
        $('#form-title').text('Edit Group Type');
        $('#submit-button').text('Update Group Type');
        $('#cancel-edit').show();
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#group-type-form').offset().top - 50
        }, 500);
    });
    
    // Cancel edit
    $('#cancel-edit').on('click', function() {
        // Reset form
        $('#form-id').val('');
        $('#form-action').val('add');
        $('#name').val('');
        $('#slug').val('');
        $('#icon').val('');
        
        // Reset form title and button
        $('#form-title').text('Add New Group Type');
        $('#submit-button').text('Add Group Type');
        $('#cancel-edit').hide();
    });
    
    // Delete functionality
    $('.delete-group-type').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this group type?')) {
            var form = $('<form>').attr({
                method: 'post',
                action: ''
            });
            form.append($('<input>').attr({type: 'hidden', name: 'action', value: 'delete'}));
            form.append($('<input>').attr({type: 'hidden', name: 'id', value: $(this).data('id')}));
            form.append($('<input>').attr({type: 'hidden', name: 'simuze_group_types_nonce', value: '<?php echo wp_create_nonce('simuze_group_types_action'); ?>'}));
            $('body').append(form);
            form.submit();
        }
    });
});
</script>

