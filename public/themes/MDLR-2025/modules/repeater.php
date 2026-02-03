<?php
/**
 * Repeater block. $block['children'] = array of repeater_row blocks.
 */
if (!isset($block)) {
    return;
}
$children = $block['children'] ?? [];
if (empty($children)) {
    return;
}
$name = $block['name'] ?? '';
if (!function_exists('render_blocks') && defined('MODULAR_PATH')) {
    require_once MODULAR_PATH . '/core/functions/blocks.php';
}
$theme_path = defined('THEMES_PATH') && defined('DEFAULT_THEME') ? THEMES_PATH . '/' . DEFAULT_THEME : '';
?>
<div class="block block-repeater block-<?php echo htmlspecialchars($name); ?>">
    <?php foreach ($children as $row): ?>
        <div class="repeater-row">
            <?php echo function_exists('render_blocks') ? render_blocks($row['children'] ?? [], $theme_path) : ''; ?>
        </div>
    <?php endforeach; ?>
</div>
