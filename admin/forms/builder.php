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
        <div id="form-builder-message" class="form-builder-message" role="status" aria-live="polite" style="display: none;"></div>

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
    gap: 0.5rem;
    flex-wrap: wrap;
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

.form-field-instance .field-label-editable {
    font-weight: 600;
}

.form-field-instance .field-type-badge {
    font-size: 0.75rem;
    color: #666;
    font-weight: normal;
}

.form-builder-message {
    padding: 0.75rem 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.form-builder-message.success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.form-builder-message.error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
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
    const label = type.charAt(0).toUpperCase() + type.slice(1);
    const field = {
        id: 'field_' + Date.now(),
        type: type,
        label: label,
        name: (type + '_' + Date.now()).toLowerCase().replace(/\s+/g, '_'),
        required: false,
        placeholder: '',
        options: []
    };
    formFields.push(field);
    renderFormFields();
}

function removeField(id) {
    formFields = formFields.filter(f => f.id !== id);
    renderFormFields();
}

function editField(id) {
    const field = formFields.find(f => f.id === id);
    if (!field) return;
    const container = document.getElementById('form-fields-list');
    const wrap = container.querySelector('[data-field-id="' + id + '"]');
    if (!wrap) return;
    const labelEl = wrap.querySelector('.field-label-editable');
    const actionsEl = wrap.querySelector('.field-actions');
    if (!labelEl || labelEl.querySelector('input')) return;
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control field-label-input';
    input.value = field.label;
    input.style.width = '100%';
    input.style.maxWidth = '240px';
    labelEl.textContent = '';
    labelEl.appendChild(input);
    input.focus();
    input.select();
    function saveLabel() {
        const newLabel = input.value.trim();
        if (newLabel) {
            field.label = newLabel;
            field.name = newLabel.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
        }
        renderFormFields();
    }
    input.addEventListener('blur', saveLabel);
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.blur();
        }
        if (e.key === 'Escape') {
            renderFormFields();
        }
    });
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
        container.innerHTML = formFields.map((field) => `
            <div class="form-field-instance" data-field-id="${field.id}">
                <div class="field-header">
                    <span class="field-label-editable">${escapeHtml(field.label)}</span>
                    <span class="field-type-badge">${escapeHtml(field.type)}</span>
                    <div class="field-actions">
                        <button type="button" class="edit-field-btn" data-id="${field.id}" title="Edit label">✎</button>
                        <button type="button" class="remove-field-btn" data-id="${field.id}" title="Delete">×</button>
                    </div>
                </div>
                <div class="field-preview">
                    ${renderFieldPreview(field)}
                </div>
            </div>
        `).join('');
        container.querySelectorAll('.edit-field-btn').forEach(btn => {
            btn.addEventListener('click', () => editField(btn.dataset.id));
        });
        container.querySelectorAll('.remove-field-btn').forEach(btn => {
            btn.addEventListener('click', () => removeField(btn.dataset.id));
        });
    }
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
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

function showFormMessage(type, text) {
    const el = document.getElementById('form-builder-message');
    if (!el) return;
    el.textContent = text;
    el.className = 'form-builder-message ' + (type === 'error' ? 'error' : 'success');
    el.style.display = 'block';
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    setTimeout(function() {
        el.style.display = 'none';
    }, 5000);
}

document.getElementById('save-form-btn').addEventListener('click', function() {
    const formData = {
        name: document.getElementById('form-name').value.trim(),
        slug: document.getElementById('form-slug').value.trim(),
        description: document.getElementById('form-description').value,
        fields: formFields,
        spam_protection: document.getElementById('form-spam-protection').checked
    };

    if (!formData.name || !formData.slug) {
        showFormMessage('error', 'Please fill in form name and slug.');
        return;
    }

    const url = '/modular/admin/api/index.php/api/v1/forms' + (<?php echo $formId ? "'/{$formId}'" : "''"; ?>);
    const method = <?php echo $formId ? "'PUT'" : "'POST'"; ?>;

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(formData),
        credentials: 'same-origin'
    })
    .then(function(response) {
        return response.text().then(function(text) {
            var result;
            try {
                result = text ? JSON.parse(text) : {};
            } catch (e) {
                throw new Error('Server returned invalid response. Try again or check the console.');
            }
            if (!response.ok) {
                var err = new Error(result.error || result.message || 'Request failed');
                err.result = result;
                err.status = response.status;
                throw err;
            }
            return result;
        });
    })
    .then(function(result) {
        if (result.error) {
            showFormMessage('error', 'Error: ' + result.error);
        } else {
            showFormMessage('success', 'Form saved successfully.');
            if (result.id && !<?php echo $formId ? 'true' : 'false'; ?>) {
                window.location.href = window.location.pathname + '?id=' + result.id;
            }
        }
    })
    .catch(function(error) {
        showFormMessage('error', error.message || 'Request failed');
    });
});

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>


