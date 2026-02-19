<?php
/**
 * CLI test: write test field data into post id=2 using the same logic as save.php.
 * Run: php admin/posts/test-save-post-2.php
 * Then open http://localhost/modular/admin/posts/edit.php?id=2 (logged in) to verify.
 */

if (php_sapi_name() !== 'cli') {
    die('Run from command line only.');
}

$base = dirname(dirname(dirname(__DIR__)));
define('MODULAR_PATH', $base);
define('ADMIN_PATH', $base . '/admin');
define('CORE_PATH', $base . '/core');

require_once ADMIN_PATH . '/config/config.php';
require_once ADMIN_PATH . '/bootstrap.php';
require_once ADMIN_PATH . '/data/init.php';

if (!isset($GLOBALS['db'])) {
    die("DB not configured.\n");
}
$db = $GLOBALS['db'];

$postId = 2;
$stmt = $db->prepare("SELECT id, post_type_id, title FROM posts WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    die("Post id=2 not found.\n");
}

$stmt = $db->prepare("SELECT mode, `schema` FROM post_types WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$post['post_type_id']]);
$pt = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pt || empty($pt['schema'])) {
    die("Post type has no schema.\n");
}

$schema = is_string($pt['schema']) ? json_decode($pt['schema'], true) : $pt['schema'];
$schemaFields = $schema['fields'] ?? [];
if (empty($schemaFields)) {
    die("Schema has no fields.\n");
}

// Build payload like the Vue app: __layout_top, __layout_blocks, __values_by_instance
$fieldNames = [];
foreach ($schemaFields as $f) {
    $type = $f['type'] ?? 'text';
    if (in_array($type, ['message', 'divider', 'accordion', 'tab'], true)) continue;
    $name = $f['name'] ?? $f['key'] ?? null;
    if ($name) $fieldNames[] = $name;
}

$instances = [];
$valuesByInstance = [];
$i = 0;
foreach ($fieldNames as $name) {
    $id = 'fi-test-' . (++$i) . '-' . $name;
    $instances[] = ['id' => $id, 'name' => $name];
    $valuesByInstance[$id] = 'Test value for ' . $name . ' - ' . date('Y-m-d H:i:s');
}

$payload = [
    '__layout_top' => [],
    '__layout_blocks' => [
        ['id' => 'block-test-1', 'numCols' => 1, 'columns' => [$instances, [], []]]
    ],
    '__values_by_instance' => $valuesByInstance,
];

// Same field requires as save.php so sanitize works for all types
require_once CORE_PATH . '/fields/FieldInterface.php';
require_once CORE_PATH . '/fields/LayoutFieldInterface.php';
require_once CORE_PATH . '/fields/FieldRegistry.php';
require_once CORE_PATH . '/fields/TextField.php';
require_once CORE_PATH . '/fields/TextareaField.php';
require_once CORE_PATH . '/fields/NumberField.php';
require_once CORE_PATH . '/fields/EmailField.php';
require_once CORE_PATH . '/fields/WysiwygField.php';
require_once CORE_PATH . '/fields/SelectField.php';
require_once CORE_PATH . '/fields/CheckboxField.php';
require_once CORE_PATH . '/fields/TrueFalseField.php';
require_once CORE_PATH . '/fields/RelationField.php';
require_once CORE_PATH . '/fields/ImageField.php';
require_once CORE_PATH . '/fields/DatePickerField.php';
require_once CORE_PATH . '/functions/field-save.php';

$rawFieldData = $payload;
$valuesByInstance = $rawFieldData['__values_by_instance'] ?? [];
$layoutBlocks = $rawFieldData['__layout_blocks'] ?? [];
$groupedByField = [];
foreach ($layoutBlocks as $block) {
    $cols = $block['columns'] ?? [];
    foreach ($cols as $colList) {
        if (!is_array($colList)) continue;
        foreach ($colList as $inst) {
            if (!is_array($inst)) continue;
            $id = $inst['id'] ?? null;
            $name = $inst['name'] ?? null;
            if (!$id || !$name) continue;
            $value = $valuesByInstance[$id] ?? null;
            if (!array_key_exists($name, $groupedByField)) $groupedByField[$name] = [];
            $groupedByField[$name][] = $value;
        }
    }
}

$data = sanitize_field_data_from_schema($schemaFields, $groupedByField);
$dataJson = json_encode($data);
$layoutOrder = ['top' => [], 'blocks' => $layoutBlocks];
$layoutJson = json_encode($layoutOrder);
$componentData = json_encode(['data' => $data]);

$stmt = $db->prepare("UPDATE posts SET data = ?, layout = ?, component_data = ?, updated_at = NOW() WHERE id = ?");
$stmt->execute([$dataJson, $layoutJson, $componentData, $postId]);

echo "OK: Post id=2 updated with test data.\n";
echo "Data keys: " . implode(', ', array_keys($data)) . "\n";
echo "Open http://localhost/modular/admin/posts/edit.php?id=2 (logged in) to verify.\n";
