<?php
/**
 * Group block. $block['children'] = array of blocks. Rendered recursively.
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
<div class="block block-group block-<?php echo htmlspecialchars($name); ?>">
    <?php echo function_exists('render_blocks') ? render_blocks($children, $theme_path) : ''; ?>
</div>
