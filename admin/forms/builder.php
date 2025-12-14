<?php
/**
 * Forms Builder Page
 * Drag-and-drop form builder
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

$page_title = 'Forms Builder';
$forms_active = true;

$formId = $_GET['id'] ?? null;
$db = $GLOBALS['db'];

// Load form if editing
$form = null;
$formFields = [];
if ($formId) {
    $stmt = $db->prepare("SELECT * FROM forms WHERE id = ?");
    $stmt->execute([$formId]);
    $form = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($form) {
        $formFields = json_decode($form['fields'], true) ?: [];
    }
}

// Field types
$fieldTypes = [
    'text' => ['label' => 'Text', 'icon' => 'text'],
    'email' => ['label' => 'Email', 'icon' => 'envelope'],
    'textarea' => ['label' => 'Textarea', 'icon' => 'align-left'],
    'select' => ['label' => 'Select', 'icon' => 'list'],
    'checkbox' => ['label' => 'Checkbox', 'icon' => 'check-square'],
    'radio' => ['label' => 'Radio', 'icon' => 'dot-circle'],
    'file' => ['label' => 'File Upload', 'icon' => 'file'],
    'date' => ['label' => 'Date', 'icon' => 'calendar'],
    'number' => ['label' => 'Number', 'icon' => 'hashtag'],
];

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h1><?php echo $form ? 'Edit Form' : 'Create Form'; ?></h1>
            <div>
                <button id="save-form-btn" class="btn btn-primary">Save Form</button>
            </div>
        </div>

        <div class="form-builder-container" style="display: grid; grid-template-columns: 250px 1fr; gap: 1rem;">
            <!-- Field Palette -->
            <div class="field-palette" style="background: #f8f9fa; padding: 1rem; border-radius: 4px;">
                <h3 style="margin-top: 0; font-size: 1rem;">Form Fields</h3>
                <div class="field-list">
                    <?php foreach ($fieldTypes as $type => $config): ?>
                    <div class="field-item" draggable="true" data-type="<?php echo $type; ?>">
                        <i class="fas fa-<?php echo $config['icon']; ?>"></i>
                        <?php echo htmlspecialchars($config['label']); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Form Canvas -->
            <div class="form-canvas" style="background: white; padding: 2rem; border: 2px dashed #ddd; border-radius: 4px; min-height: 400px;">
                <div id="form-fields-list">
                    <?php if (empty($formFields)): ?>
                    <div class="empty-state" style="text-align: center; padding: 3rem; color: #999;">
                        <i class="fas fa-mouse-pointer" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>Drag fields here to build your form</p>
                    </div>
                    <?php else: ?>
                        <!-- Fields will be rendered here -->
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Form Settings -->
        <div class="card mt-4">
            <h3>Form Settings</h3>
            <div class="form-group">
                <label>Form Name</label>
                <input type="text" id="form-name" value="<?php echo htmlspecialchars($form['name'] ?? ''); ?>" class="form-control">
            </div>
            <div class="form-group">
                <label>Form Slug</label>
                <input type="text" id="form-slug" value="<?php echo htmlspecialchars($form['slug'] ?? ''); ?>" class="form-control">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="form-description" class="form-control" rows="3"><?php echo htmlspecialchars($form['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="form-spam-protection" <?php echo isset($form['spam_protection']) && $form['spam_protection'] ? 'checked' : ''; ?>>
                    Enable Spam Protection (Honeypot + Rate Limiting)
                </label>
            </div>
        </div>
    </div>
</div>

<style>
.field-item {
    padding: 0.75rem;
    margin: 0.5rem 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: move;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.field-item:hover {
    background: #e3f2fd;
    border-color: #2196f3;
}

.form-canvas .form-field-instance {
    padding: 1rem;
    margin: 0.5rem 0;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
    position: relative;
}

.form-field-instance .field-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.form-field-instance .field-actions button {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    border: none;
    background: #e0e0e0;
    cursor: pointer;
    border-radius: 3px;
    margin-left: 0.25rem;
}

.form-field-instance .field-actions button:hover {
    background: #d0d0d0;
}
</style>

<script>
let formFields = <?php echo json_encode($formFields); ?>;
let draggedField = null;

document.addEventListener('DOMContentLoaded', function() {
    renderFormFields();
    setupDragAndDrop();
});

function setupDragAndDrop() {
    document.querySelectorAll('.field-item').forEach(item => {
        item.addEventListener('dragstart', (e) => {
            draggedField = e.target.dataset.type;
            e.target.style.opacity = '0.5';
        });
        item.addEventListener('dragend', (e) => {
            e.target.style.opacity = '1';
        });
    });

    const canvas = document.querySelector('.form-canvas');
    canvas.addEventListener('dragover', (e) => {
        e.preventDefault();
        canvas.style.borderColor = '#2196f3';
    });
    canvas.addEventListener('dragleave', () => {
        canvas.style.borderColor = '#ddd';
    });
    canvas.addEventListener('drop', (e) => {
        e.preventDefault();
        canvas.style.borderColor = '#ddd';
        if (draggedField) {
            addField(draggedField);
            draggedField = null;
        }
    });
}

function addField(type) {
    const field = {
        id: 'field_' + Date.now(),
        type: type,
        label: type.charAt(0).toUpperCase() + type.slice(1),
        name: type + '_' + Date.now(),
        required: false,
        placeholder: '',
        options: []
    };
    
    formFields.push(field);
    renderFormFields();
    editField(field.id);
}

function removeField(id) {
    formFields = formFields.filter(f => f.id !== id);
    renderFormFields();
}

function editField(id) {
    const field = formFields.find(f => f.id === id);
    if (!field) return;
    
    const newLabel = prompt('Field Label:', field.label);
    if (newLabel !== null) {
        field.label = newLabel;
        field.name = newLabel.toLowerCase().replace(/\s+/g, '_');
        renderFormFields();
    }
}

function renderFormFields() {
    const container = document.getElementById('form-fields-list');
    
    if (formFields.length === 0) {
        container.innerHTML = `
            <div class="empty-state" style="text-align: center; padding: 3rem; color: #999;">
                <i class="fas fa-mouse-pointer" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>Drag fields here to build your form</p>
            </div>
        `;
    } else {
        container.innerHTML = formFields.map((field, index) => `
            <div class="form-field-instance">
                <div class="field-header">
                    <strong>${field.label} (${field.type})</strong>
                    <div class="field-actions">
                        <button onclick="editField('${field.id}')" title="Edit">✎</button>
                        <button onclick="removeField('${field.id}')" title="Delete">×</button>
                    </div>
                </div>
                <div class="field-preview">
                    ${renderFieldPreview(field)}
                </div>
            </div>
        `).join('');
    }
}

function renderFieldPreview(field) {
    switch (field.type) {
        case 'text':
        case 'email':
        case 'date':
        case 'number':
            return `<input type="${field.type}" placeholder="${field.placeholder}" ${field.required ? 'required' : ''} disabled>`;
        case 'textarea':
            return `<textarea placeholder="${field.placeholder}" ${field.required ? 'required' : ''} disabled></textarea>`;
        case 'select':
            return `<select ${field.required ? 'required' : ''} disabled><option>Select option...</option></select>`;
        case 'checkbox':
            return `<input type="checkbox" ${field.required ? 'required' : ''} disabled> ${field.label}`;
        case 'radio':
            return `<input type="radio" ${field.required ? 'required' : ''} disabled> ${field.label}`;
        default:
            return `<input type="text" placeholder="${field.placeholder}" disabled>`;
    }
}

document.getElementById('save-form-btn').addEventListener('click', function() {
    const formData = {
        name: document.getElementById('form-name').value,
        slug: document.getElementById('form-slug').value,
        description: document.getElementById('form-description').value,
        fields: formFields,
        spam_protection: document.getElementById('form-spam-protection').checked
    };
    
    if (!formData.name || !formData.slug) {
        alert('Please fill in form name and slug');
        return;
    }
    
    const url = '/modular/admin/api/index.php/api/v1/forms' + (<?php echo $formId ? "'/{$formId}'" : "''"; ?>);
    const method = <?php echo $formId ? "'PUT'" : "'POST'"; ?>;
    
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
            alert('Form saved successfully!');
            if (result.id && !<?php echo $formId ? 'true' : 'false'; ?>) {
                window.location.href = window.location.href + '&id=' + result.id;
            }
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
});

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>

