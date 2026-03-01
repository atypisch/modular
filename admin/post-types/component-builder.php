<?php
/**
 * Component Builder Page
 * Drag-and-drop interface for flexible components
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/classes/ComponentTypes.php';

use Modular\Components\ComponentTypes;

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$page_title = 'Component Builder';
$post_types_active = true;

$componentTypes = ComponentTypes::getTypes();
$postTypeId = $_GET['post_type_id'] ?? null;
$postId = $_GET['post_id'] ?? null;

// Load existing component data if editing
$componentData = [];
if ($postId) {
    $db = $GLOBALS['db'];
    $stmt = $db->prepare("SELECT component_data FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ($result && $result['component_data']) {
        $componentData = json_decode($result['component_data'], true) ?: [];
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h1>Component Builder</h1>
            <div>
                <button id="save-btn" class="btn btn-primary">Save</button>
                <button id="preview-btn" class="btn btn-secondary">Preview</button>
            </div>
        </div>

        <div class="component-builder-container" style="display: grid; grid-template-columns: 250px 1fr 300px; gap: 1rem; min-height: 600px;">
            <!-- Component Palette -->
            <div class="component-palette" style="background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-y: auto;">
                <h3 style="margin-top: 0; font-size: 1rem;">Components</h3>
                <div class="component-categories">
                    <div class="category" data-category="basic">
                        <h4 style="font-size: 0.9rem; margin: 0.5rem 0;">Basic Fields</h4>
                        <div class="component-list">
                            <?php
                            $basicTypes = ['text', 'textarea', 'wysiwyg', 'title', 'slug', 'excerpt'];
                            foreach ($basicTypes as $type):
                                if (isset($componentTypes[$type])):
                            ?>
                            <div class="component-item" draggable="true" data-type="<?php echo $type; ?>">
                                <i class="fas fa-<?php echo $componentTypes[$type]['icon'] ?? 'square'; ?>"></i>
                                <?php echo htmlspecialchars($componentTypes[$type]['label']); ?>
                            </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </div>
                    
                    <div class="category" data-category="media">
                        <h4 style="font-size: 0.9rem; margin: 0.5rem 0;">Media</h4>
                        <div class="component-list">
                            <?php
                            $mediaTypes = ['image', 'gallery', 'file', 'video'];
                            foreach ($mediaTypes as $type):
                                if (isset($componentTypes[$type])):
                            ?>
                            <div class="component-item" draggable="true" data-type="<?php echo $type; ?>">
                                <i class="fas fa-<?php echo $componentTypes[$type]['icon'] ?? 'square'; ?>"></i>
                                <?php echo htmlspecialchars($componentTypes[$type]['label']); ?>
                            </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </div>
                    
                    <div class="category" data-category="blocks">
                        <h4 style="font-size: 0.9rem; margin: 0.5rem 0;">Content Blocks</h4>
                        <div class="component-list">
                            <?php
                            $blockTypes = ['hero', 'cta', 'quote', 'testimonial', 'gallery_grid', 'slider'];
                            foreach ($blockTypes as $type):
                                if (isset($componentTypes[$type])):
                            ?>
                            <div class="component-item" draggable="true" data-type="<?php echo $type; ?>">
                                <i class="fas fa-<?php echo $componentTypes[$type]['icon'] ?? 'square'; ?>"></i>
                                <?php echo htmlspecialchars($componentTypes[$type]['label']); ?>
                            </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Canvas -->
            <div class="component-canvas" style="background: white; padding: 1rem; border: 2px dashed #ddd; border-radius: 4px; min-height: 500px; position: relative;">
                <div id="canvas-drop-zone" style="min-height: 100%;">
                    <?php if (empty($componentData)): ?>
                    <div class="empty-state" style="text-align: center; padding: 3rem; color: #999;">
                        <i class="fas fa-mouse-pointer" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>Drag components here to build your page</p>
                    </div>
                    <?php else: ?>
                    <div id="components-list">
                        <!-- Components will be rendered here -->
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Properties Panel -->
            <div class="properties-panel" style="background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-y: auto;">
                <h3 style="margin-top: 0; font-size: 1rem;">Properties</h3>
                <div id="properties-content">
                    <p style="color: #999; font-size: 0.9rem;">Select a component to edit its properties</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.component-item {
    padding: 0.5rem;
    margin: 0.25rem 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: move;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.component-item:hover {
    background: #e3f2fd;
    border-color: #2196f3;
}

.component-item.dragging {
    opacity: 0.5;
}

.component-canvas .component-instance {
    padding: 1rem;
    margin: 0.5rem 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    position: relative;
    cursor: pointer;
}

.component-canvas .component-instance:hover {
    border-color: #2196f3;
}

.component-canvas .component-instance.selected {
    border-color: #2196f3;
    box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
}

.component-instance .component-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
}

.component-instance .component-actions {
    display: flex;
    gap: 0.5rem;
}

.component-instance .component-actions button {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    border: none;
    background: #f5f5f5;
    cursor: pointer;
    border-radius: 3px;
}

.component-instance .component-actions button:hover {
    background: #e0e0e0;
}

.drop-zone-active {
    border-color: #2196f3 !important;
    background: #e3f2fd !important;
}

.properties-panel .form-group {
    margin-bottom: 1rem;
}

.properties-panel label {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.properties-panel input,
.properties-panel textarea,
.properties-panel select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}
</style>

<script>
// Component Builder JavaScript
let components = <?php echo json_encode($componentData); ?>;
let selectedComponent = null;
let draggedComponent = null;

const componentTypes = <?php echo json_encode($componentTypes); ?>;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    renderComponents();
    setupDragAndDrop();
    setupEventListeners();
});

function setupDragAndDrop() {
    // Make component items draggable
    document.querySelectorAll('.component-item').forEach(item => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
    });

    // Canvas drop zone
    const canvas = document.getElementById('canvas-drop-zone');
    canvas.addEventListener('dragover', handleDragOver);
    canvas.addEventListener('drop', handleDrop);
    canvas.addEventListener('dragleave', handleDragLeave);
}

function handleDragStart(e) {
    draggedComponent = e.target.dataset.type;
    e.target.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd(e) {
    e.target.classList.remove('dragging');
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    e.currentTarget.classList.add('drop-zone-active');
}

function handleDragLeave(e) {
    e.currentTarget.classList.remove('drop-zone-active');
}

function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('drop-zone-active');
    
    if (draggedComponent) {
        addComponent(draggedComponent);
        draggedComponent = null;
    }
}

function addComponent(type) {
    const component = {
        id: 'comp_' + Date.now(),
        type: type,
        data: {},
        settings: {}
    };
    
    components.push(component);
    renderComponents();
    selectComponent(component.id);
}

function removeComponent(id) {
    components = components.filter(c => c.id !== id);
    renderComponents();
    selectedComponent = null;
    updatePropertiesPanel();
}

function moveComponent(fromIndex, toIndex) {
    const [removed] = components.splice(fromIndex, 1);
    components.splice(toIndex, 0, removed);
    renderComponents();
}

function selectComponent(id) {
    selectedComponent = components.find(c => c.id === id);
    updatePropertiesPanel();
    renderComponents();
}

function updatePropertiesPanel() {
    const panel = document.getElementById('properties-content');
    
    if (!selectedComponent) {
        panel.innerHTML = '<p style="color: #999; font-size: 0.9rem;">Select a component to edit its properties</p>';
        return;
    }
    
    const type = componentTypes[selectedComponent.type];
    if (!type) return;
    
    let html = `<h4 style="margin-top: 0;">${type.label}</h4>`;
    html += `<input type="hidden" id="comp-id" value="${selectedComponent.id}">`;
    
    // Render fields based on schema
    const schema = type.schema || {};
    for (const [key, config] of Object.entries(schema)) {
        if (key === 'name' || key === 'label') continue;
        
        const value = selectedComponent.data[key] ?? config.default ?? '';
        html += renderField(key, config, value);
    }
    
    html += `<button onclick="saveComponentProperties()" class="btn btn-primary" style="margin-top: 1rem;">Save Properties</button>`;
    
    panel.innerHTML = html;
}

function renderField(name, config, value) {
    const label = name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    let html = `<div class="form-group">`;
    html += `<label for="prop-${name}">${label}</label>`;
    
    if (config.type === 'boolean') {
        html += `<input type="checkbox" id="prop-${name}" ${value ? 'checked' : ''}>`;
    } else if (config.type === 'array') {
        html += `<textarea id="prop-${name}" rows="3">${Array.isArray(value) ? value.join('\n') : ''}</textarea>`;
    } else if (config.type === 'text' || config.type === 'string') {
        html += `<input type="text" id="prop-${name}" value="${escapeHtml(value)}">`;
    } else {
        html += `<input type="text" id="prop-${name}" value="${escapeHtml(value)}">`;
    }
    
    html += `</div>`;
    return html;
}

function saveComponentProperties() {
    if (!selectedComponent) return;
    
    const panel = document.getElementById('properties-content');
    const inputs = panel.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        if (input.id.startsWith('prop-')) {
            const key = input.id.replace('prop-', '');
            if (input.type === 'checkbox') {
                selectedComponent.data[key] = input.checked;
            } else if (input.type === 'textarea') {
                selectedComponent.data[key] = input.value.split('\n').filter(v => v.trim());
            } else {
                selectedComponent.data[key] = input.value;
            }
        }
    });
    
    renderComponents();
}

function renderComponents() {
    const container = document.getElementById('canvas-drop-zone');
    const list = document.getElementById('components-list') || document.createElement('div');
    list.id = 'components-list';
    
    if (components.length === 0) {
        list.innerHTML = `
            <div class="empty-state" style="text-align: center; padding: 3rem; color: #999;">
                <i class="fas fa-mouse-pointer" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>Drag components here to build your page</p>
            </div>
        `;
    } else {
        list.innerHTML = components.map((comp, index) => {
            const type = componentTypes[comp.type];
            const isSelected = selectedComponent && selectedComponent.id === comp.id;
            
            return `
                <div class="component-instance ${isSelected ? 'selected' : ''}" onclick="selectComponent('${comp.id}')">
                    <div class="component-header">
                        <strong>${type ? type.label : comp.type}</strong>
                        <div class="component-actions">
                            <button onclick="event.stopPropagation(); moveComponent(${index}, ${Math.max(0, index - 1)})" title="Move Up">↑</button>
                            <button onclick="event.stopPropagation(); moveComponent(${index}, ${Math.min(components.length - 1, index + 1)})" title="Move Down">↓</button>
                            <button onclick="event.stopPropagation(); removeComponent('${comp.id}')" title="Delete">×</button>
                        </div>
                    </div>
                    <div class="component-preview">
                        ${renderComponentPreview(comp, type)}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    if (!container.querySelector('#components-list')) {
        container.innerHTML = '';
        container.appendChild(list);
    }
}

function renderComponentPreview(comp, type) {
    if (!type) return '<p>Unknown component type</p>';
    
    // Simple preview based on type
    if (comp.data.title) return `<strong>${escapeHtml(comp.data.title)}</strong>`;
    if (comp.data.text) return `<p>${escapeHtml(comp.data.text)}</p>`;
    return `<p style="color: #999;">${type.label} - Configure in properties panel</p>`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function setupEventListeners() {
    document.getElementById('save-btn').addEventListener('click', saveComponents);
    document.getElementById('preview-btn').addEventListener('click', previewComponents);
}

function saveComponents() {
    const data = {
        post_type_id: <?php echo $postTypeId ?: 'null'; ?>,
        post_id: <?php echo $postId ?: 'null'; ?>,
        component_data: components
    };
    
    fetch('/modular/admin/api/index.php/api/v1/posts' + (data.post_id ? '/' + data.post_id : ''), {
        method: data.post_id ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + getCookie('auth_token')
        },
        body: JSON.stringify({
            post_type_id: data.post_type_id,
            title: 'Component Builder Post',
            component_data: components
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.error) {
            (window.ModularModal && window.ModularModal.alert('Error: ' + result.error));
        } else {
            (window.ModularModal && window.ModularModal.alert('Opgeslagen!'));
            if (result.id && !data.post_id) {
                window.location.href = window.location.href + '&post_id=' + result.id;
            }
        }
    })
    .catch(error => {
        (window.ModularModal && window.ModularModal.alert('Error: ' + error.message));
    });
}

function previewComponents() {
    // Open preview in new window
    const preview = window.open('', 'preview');
    preview.document.write(`
        <html>
            <head><title>Preview</title></head>
            <body>
                ${components.map(comp => {
                    const type = componentTypes[comp.type];
                    return renderComponentPreview(comp, type);
                }).join('')}
            </body>
        </html>
    `);
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>


