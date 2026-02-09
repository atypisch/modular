<?php
/**
 * Field Renderer Test Page – Renders every Modular CMS field type with PrimeVue.
 * Use to verify resolveFieldComponent() and Vue renderer coverage.
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

$page_title = 'Field Renderer Test';

// Schema with one of each field type (flat, no group/repeater for simplicity)
$schemaFields = [
    ['type' => 'text', 'name' => 'f_text', 'label' => 'Text', 'placeholder' => 'Enter text', 'required' => true],
    ['type' => 'email', 'name' => 'f_email', 'label' => 'Email', 'placeholder' => 'email@example.com'],
    ['type' => 'url', 'name' => 'f_url', 'label' => 'URL', 'placeholder' => 'https://'],
    ['type' => 'textarea', 'name' => 'f_textarea', 'label' => 'Textarea', 'rows' => 3],
    ['type' => 'wysiwyg', 'name' => 'f_wysiwyg', 'label' => 'WYSIWYG', 'toolbar' => 'full'],
    ['type' => 'number', 'name' => 'f_number', 'label' => 'Number', 'min' => 0, 'max' => 100, 'step' => 1],
    ['type' => 'range', 'name' => 'f_range', 'label' => 'Range', 'min' => 0, 'max' => 100],
    ['type' => 'password', 'name' => 'f_password', 'label' => 'Password'],
    ['type' => 'search', 'name' => 'f_search', 'label' => 'Search', 'placeholder' => 'Search'],
    ['type' => 'select', 'name' => 'f_select', 'label' => 'Select', 'choices' => ['a' => 'Option A', 'b' => 'Option B', 'c' => 'Option C'], 'allow_null' => true],
    ['type' => 'radio', 'name' => 'f_radio', 'label' => 'Radio', 'choices' => ['x' => 'X', 'y' => 'Y']],
    ['type' => 'button_group', 'name' => 'f_button_group', 'label' => 'Button Group', 'choices' => ['left' => 'Left', 'center' => 'Center', 'right' => 'Right']],
    ['type' => 'checkbox', 'name' => 'f_checkbox', 'label' => 'Checkbox (single)'],
    ['type' => 'true_false', 'name' => 'f_true_false', 'label' => 'True/False'],
    ['type' => 'date_picker', 'name' => 'f_date', 'label' => 'Date'],
    ['type' => 'time_picker', 'name' => 'f_time', 'label' => 'Time'],
    ['type' => 'date_time_picker', 'name' => 'f_datetime', 'label' => 'Date & Time'],
    ['type' => 'post_object', 'name' => 'f_post_object', 'label' => 'Post Object', 'options' => [['label' => 'Post 1', 'value' => 1], ['label' => 'Post 2', 'value' => 2]]],
    ['type' => 'relationship', 'name' => 'f_relationship', 'label' => 'Relationship', 'options' => [['label' => 'Post A', 'value' => 1], ['label' => 'Post B', 'value' => 2]]],
    ['type' => 'user', 'name' => 'f_user', 'label' => 'User', 'options' => [['label' => 'Admin', 'value' => 1]]],
    ['type' => 'image', 'name' => 'f_image', 'label' => 'Image'],
    ['type' => 'file', 'name' => 'f_file', 'label' => 'File'],
    ['type' => 'gallery', 'name' => 'f_gallery', 'label' => 'Gallery'],
    ['type' => 'rating', 'name' => 'f_rating', 'label' => 'Rating', 'stars' => 5, 'instructions' => '1–5 stars'],
    ['type' => 'color', 'name' => 'f_color', 'label' => 'Color', 'format' => 'hex'],
    ['type' => 'listbox', 'name' => 'f_listbox', 'label' => 'Listbox', 'choices' => ['red' => 'Red', 'green' => 'Green', 'blue' => 'Blue'], 'filter' => true],
    ['type' => 'autocomplete', 'name' => 'f_autocomplete', 'label' => 'AutoComplete', 'placeholder' => 'Type to search', 'suggestions' => [['label' => 'Apple', 'value' => 'apple'], ['label' => 'Banana', 'value' => 'banana']], 'dropdown' => true],
    ['type' => 'chips', 'name' => 'f_chips', 'label' => 'Chips (tags)', 'placeholder' => 'Add tag'],
    ['type' => 'text', 'name' => 'f_invalid_demo', 'label' => 'Invalid demo', 'placeholder' => 'Required', 'required' => true, 'invalid' => true, 'validationMessage' => 'This field is required.'],
    ['type' => 'message', 'label' => 'This is a message (utility)', 'instructions' => 'No storage.', 'tooltip' => 'Helper message'],
    ['type' => 'accordion', 'label' => 'Section (accordion)', 'instructions' => 'Visual only.'],
    ['type' => 'tab', 'label' => 'Tab label'],
    ['type' => 'divider'],
    ['type' => 'hidden', 'name' => 'f_hidden', 'label' => 'Hidden', 'default_value' => 'secret'],
];

$fieldData = [
    'f_text' => 'Sample text',
    'f_number' => 50,
    'f_range' => 25,
    'f_checkbox' => true,
    'f_true_false' => false,
    'f_select' => 'b',
    'f_radio' => 'x',
    'f_button_group' => 'center',
    'f_rating' => 3,
    'f_color' => '#2d8016',
    'f_relationship' => [1, 2],
    'f_chips' => ['tag1', 'tag2'],
];

require_once ADMIN_PATH . '/templates/header.php';
?>
<link rel="stylesheet" href="/modular/admin/assets/css/post-edit.css">
<div class="main-content" style="max-width: 900px; margin: 0 auto;">
    <h1>Field Renderer Test</h1>
    <p class="help-text">Every Modular CMS field type rendered with PrimeVue. Check styling, labels, hints, and validation.</p>

    <div id="field-renderer-todo-list" class="card" style="margin-bottom: 1rem;">
        <h3 style="margin: 0 0 0.5rem 0;">Live TODO / Issues</h3>
        <ul id="field-renderer-todo-items" style="margin: 0; padding-left: 1.25rem; font-size: 0.9rem;"></ul>
    </div>

    <form id="field-renderer-test-form" class="card">
        <div id="post-edit-fields-vue-app" class="modular-field-renderer-container"></div>
        <input type="hidden" name="field_data_json" id="field_data_json" value="">
        <div style="margin-top: 1rem;">
            <button type="button" id="btn-dump-data" class="btn btn-secondary btn-sm">Dump field data (console)</button>
        </div>
    </form>
</div>

<script>
window.MODULAR_FIELD_SCHEMA = { fields: <?php echo json_encode($schemaFields); ?> };
window.MODULAR_FIELD_DATA = <?php echo json_encode($fieldData); ?>;
window.MODULAR_FIELD_OPTIONS = {};
</script>
<script src="/modular/admin/assets/js/field-renderer/resolveFieldComponent.js?v=1"></script>
<script src="/modular/admin/assets/js/field-renderer/field-renderer-app.js?v=1"></script>
<script>
(function() {
    function reportTodo(items) {
        var ul = document.getElementById('field-renderer-todo-items');
        if (!ul) return;
        ul.innerHTML = '';
        items.forEach(function(t) {
            var li = document.createElement('li');
            li.textContent = t;
            ul.appendChild(li);
        });
    }

    function detectIssues() {
        var todos = [];
        var resolver = window.ModularFieldRenderer;
        if (!resolver) {
            todos.push('ModularFieldRenderer (resolveFieldComponent.js) not loaded.');
            reportTodo(todos);
            return;
        }
        var types = ['text','email','url','textarea','wysiwyg','number','range','password','select','radio','button_group','checkbox','true_false','date_picker','time_picker','date_time_picker','post_object','relationship','user','image','file','gallery','rating','color','listbox','autocomplete','chips','message','accordion','tab','divider','hidden'];
        types.forEach(function(t) {
            var r = resolver.resolveFieldComponent(t, { name: 'test', label: 'Test' });
            if (!r.component && ['message','accordion','tab','divider'].indexOf(t) === -1) {
                todos.push('Missing component for type: ' + t);
            }
            if (r.tag === 'p-inputtext' && ['wysiwyg','number','rating','color'].indexOf(t) !== -1) {
                todos.push('Wrong component for ' + t + ' (expected specific component, got InputText)');
            }
            if (!r.styledOptions || typeof r.styledOptions.class === 'undefined') {
                todos.push('styledOptions missing for type: ' + t);
            }
        });
        if (!window.PrimeVue || !window.PrimeVue.Editor) {
            todos.push('PrimeVue or Editor not available – WYSIWYG may not render.');
        }
        if (!window.PrimeVue || !window.PrimeVue.Calendar) {
            todos.push('PrimeVue Calendar not available – date/time fields may not render.');
        }
        if (todos.length === 0) {
            todos.push('No automatic issues detected. Manually verify each field above.');
        }
        reportTodo(todos);
    }

    setTimeout(function() {
        detectIssues();
        var dumpBtn = document.getElementById('btn-dump-data');
        if (dumpBtn) {
            dumpBtn.addEventListener('click', function() {
                var inst = window.ModularFieldRendererApp && window.ModularFieldRendererApp._instance;
                if (inst && inst.fieldData) {
                    var plain = {};
                    for (var k in inst.fieldData) {
                        if (Object.prototype.hasOwnProperty.call(inst.fieldData, k)) {
                            plain[k] = inst.fieldData[k];
                        }
                    }
                    console.log('Field data:', plain);
                } else {
                    console.warn('Field renderer not mounted or no fieldData.');
                }
            });
        }
    }, 500);
})();
</script>
<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
