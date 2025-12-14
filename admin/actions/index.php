<?php
/**
 * Actions Builder UI
 * Visual workflow builder for actions and automation
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$page_title = 'Actions & Automation';
$actions_active = true;

$db = $GLOBALS['db'];

// Load existing actions
$actions = [];
try {
    $stmt = $db->query("SELECT * FROM actions ORDER BY created_at DESC");
    $actions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading actions: " . $e->getMessage());
}

// Trigger types
$triggerTypes = [
    'form_submit' => ['label' => 'Form Submitted', 'icon' => 'wpforms'],
    'post_created' => ['label' => 'Post Created', 'icon' => 'file-alt'],
    'post_updated' => ['label' => 'Post Updated', 'icon' => 'edit'],
    'post_published' => ['label' => 'Post Published', 'icon' => 'check-circle'],
    'user_registered' => ['label' => 'User Registered', 'icon' => 'user-plus'],
    'media_uploaded' => ['label' => 'Media Uploaded', 'icon' => 'upload'],
];

// Action types
$actionTypes = [
    'send_email' => ['label' => 'Send Email', 'icon' => 'envelope'],
    'webhook' => ['label' => 'Call Webhook', 'icon' => 'link'],
    'create_post' => ['label' => 'Create Post', 'icon' => 'file-alt'],
    'update_status' => ['label' => 'Update Status', 'icon' => 'toggle-on'],
    'add_tag' => ['label' => 'Add Tag', 'icon' => 'tag'],
    'redirect' => ['label' => 'Redirect', 'icon' => 'arrow-right'],
    'show_message' => ['label' => 'Show Message', 'icon' => 'comment'],
    'run_hook' => ['label' => 'Run Hook', 'icon' => 'code'],
];

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h1>Actions & Automation</h1>
            <button id="create-action-btn" class="btn btn-primary">Create New Action</button>
        </div>

        <div id="actions-list">
            <?php if (empty($actions)): ?>
                <p>No actions configured. Create your first automation workflow.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Trigger</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actions as $action): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($action['name']); ?></td>
                            <td><?php echo htmlspecialchars($triggerTypes[$action['trigger_type']]['label'] ?? $action['trigger_type']); ?></td>
                            <td><?php echo htmlspecialchars($actionTypes[$action['action_type']]['label'] ?? $action['action_type']); ?></td>
                            <td>
                                <span class="badge <?php echo $action['enabled'] ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $action['enabled'] ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </td>
                            <td><?php echo $action['priority']; ?></td>
                            <td>
                                <button onclick="editAction(<?php echo $action['id']; ?>)" class="btn btn-sm">Edit</button>
                                <button onclick="toggleAction(<?php echo $action['id']; ?>, <?php echo $action['enabled'] ? 0 : 1; ?>)" class="btn btn-sm">
                                    <?php echo $action['enabled'] ? 'Disable' : 'Enable'; ?>
                                </button>
                                <button onclick="deleteAction(<?php echo $action['id']; ?>)" class="btn btn-sm btn-danger">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Action Builder Modal -->
<div id="action-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2>Create Action</h2>
            <button onclick="closeModal()" class="close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="action-form">
                <input type="hidden" id="action-id">
                
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" id="action-name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>When (Trigger)</label>
                    <select id="trigger-type" class="form-control" required>
                        <option value="">Select trigger...</option>
                        <?php foreach ($triggerTypes as $key => $trigger): ?>
                        <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($trigger['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="trigger-config" class="form-group" style="display: none;">
                    <label>Trigger Conditions</label>
                    <div id="trigger-conditions"></div>
                    <button type="button" onclick="addTriggerCondition()" class="btn btn-sm">Add Condition</button>
                </div>
                
                <div class="form-group">
                    <label>Then (Action)</label>
                    <select id="action-type" class="form-control" required>
                        <option value="">Select action...</option>
                        <?php foreach ($actionTypes as $key => $action): ?>
                        <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($action['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="action-config" class="form-group" style="display: none;">
                    <label>Action Configuration</label>
                    <div id="action-config-fields"></div>
                </div>
                
                <div class="form-group">
                    <label>Priority</label>
                    <input type="number" id="action-priority" class="form-control" value="10" min="1" max="100">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="action-enabled" checked>
                        Enabled
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Action</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 8px;
    padding: 0;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 1rem;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 1rem;
}

.close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}
</style>

<script>
document.getElementById('create-action-btn').addEventListener('click', function() {
    document.getElementById('action-modal').style.display = 'flex';
    document.getElementById('action-form').reset();
    document.getElementById('action-id').value = '';
});

document.getElementById('trigger-type').addEventListener('change', function() {
    const triggerType = this.value;
    const configDiv = document.getElementById('trigger-config');
    if (triggerType) {
        configDiv.style.display = 'block';
        updateTriggerConfig(triggerType);
    } else {
        configDiv.style.display = 'none';
    }
});

document.getElementById('action-type').addEventListener('change', function() {
    const actionType = this.value;
    const configDiv = document.getElementById('action-config');
    if (actionType) {
        configDiv.style.display = 'block';
        updateActionConfig(actionType);
    } else {
        configDiv.style.display = 'none';
    }
});

function updateTriggerConfig(triggerType) {
    const container = document.getElementById('trigger-conditions');
    container.innerHTML = '<p class="text-muted">Configure trigger conditions (optional)</p>';
}

function updateActionConfig(actionType) {
    const container = document.getElementById('action-config-fields');
    let html = '';
    
    switch(actionType) {
        case 'send_email':
            html = `
                <input type="email" id="action-to" class="form-control" placeholder="To (email)" required>
                <input type="text" id="action-subject" class="form-control mt-2" placeholder="Subject" required>
                <textarea id="action-body" class="form-control mt-2" rows="5" placeholder="Body (use {field} for placeholders)" required></textarea>
            `;
            break;
        case 'webhook':
            html = `
                <input type="url" id="action-url" class="form-control" placeholder="Webhook URL" required>
            `;
            break;
        case 'create_post':
            html = `
                <input type="number" id="action-post-type-id" class="form-control" placeholder="Post Type ID" required>
                <input type="text" id="action-title" class="form-control mt-2" placeholder="Title" required>
                <select id="action-status" class="form-control mt-2">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            `;
            break;
        case 'update_status':
            html = `
                <select id="action-status" class="form-control">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
            `;
            break;
        case 'add_tag':
            html = `
                <input type="text" id="action-tag" class="form-control" placeholder="Tag name" required>
            `;
            break;
        case 'redirect':
            html = `
                <input type="url" id="action-url" class="form-control" placeholder="Redirect URL" required>
            `;
            break;
        case 'show_message':
            html = `
                <textarea id="action-message" class="form-control" rows="3" placeholder="Message to display" required></textarea>
            `;
            break;
        case 'run_hook':
            html = `
                <input type="text" id="action-hook" class="form-control" placeholder="Hook name" required>
            `;
            break;
    }
    
    container.innerHTML = html;
}

function addTriggerCondition() {
    // Add condition UI
}

function closeModal() {
    document.getElementById('action-modal').style.display = 'none';
}

document.getElementById('action-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: document.getElementById('action-name').value,
        trigger_type: document.getElementById('trigger-type').value,
        action_type: document.getElementById('action-type').value,
        priority: parseInt(document.getElementById('action-priority').value),
        enabled: document.getElementById('action-enabled').checked ? 1 : 0
    };
    
    // Get action config based on type
    const actionType = formData.action_type;
    const actionConfig = {};
    
    switch(actionType) {
        case 'send_email':
            actionConfig.to = document.getElementById('action-to').value;
            actionConfig.subject = document.getElementById('action-subject').value;
            actionConfig.body = document.getElementById('action-body').value;
            break;
        case 'webhook':
        case 'redirect':
            actionConfig.url = document.getElementById('action-url').value;
            break;
        case 'create_post':
            actionConfig.post_type_id = parseInt(document.getElementById('action-post-type-id').value);
            actionConfig.title = document.getElementById('action-title').value;
            actionConfig.status = document.getElementById('action-status').value;
            break;
        case 'update_status':
            actionConfig.status = document.getElementById('action-status').value;
            break;
        case 'add_tag':
            actionConfig.tag = document.getElementById('action-tag').value;
            break;
        case 'show_message':
            actionConfig.message = document.getElementById('action-message').value;
            break;
        case 'run_hook':
            actionConfig.hook = document.getElementById('action-hook').value;
            break;
    }
    
    formData.action_config = actionConfig;
    formData.trigger_config = {};
    
    const actionId = document.getElementById('action-id').value;
    const url = '/modular/admin/api/index.php/api/v1/actions' + (actionId ? '/' + actionId : '');
    const method = actionId ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + getCookie('auth_token')
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.error) {
            alert('Error: ' + result.error);
        } else {
            closeModal();
            location.reload();
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
});

function editAction(id) {
    // Load action and populate form
    fetch('/modular/admin/api/index.php/api/v1/actions/' + id, {
        headers: {
            'Authorization': 'Bearer ' + getCookie('auth_token')
        }
    })
    .then(response => response.json())
    .then(action => {
        document.getElementById('action-id').value = action.id;
        document.getElementById('action-name').value = action.name;
        document.getElementById('trigger-type').value = action.trigger_type;
        document.getElementById('action-type').value = action.action_type;
        document.getElementById('action-priority').value = action.priority;
        document.getElementById('action-enabled').checked = action.enabled;
        
        // Update config fields
        updateActionConfig(action.action_type);
        
        // Populate config values
        const config = action.action_config || {};
        // ... populate fields based on action type
        
        document.getElementById('action-modal').style.display = 'flex';
    });
}

function toggleAction(id, enabled) {
    fetch('/modular/admin/api/index.php/api/v1/actions/' + id, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + getCookie('auth_token')
        },
        body: JSON.stringify({ enabled: enabled })
    })
    .then(() => location.reload());
}

function deleteAction(id) {
    if (!confirm('Are you sure you want to delete this action?')) return;
    
    fetch('/modular/admin/api/index.php/api/v1/actions/' + id, {
        method: 'DELETE',
        headers: {
            'Authorization': 'Bearer ' + getCookie('auth_token')
        }
    })
    .then(() => location.reload());
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>

