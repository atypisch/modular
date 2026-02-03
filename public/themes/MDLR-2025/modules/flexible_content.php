<?php
/**
 * Flexible content. $block['children'] = array of flexible_layout blocks.
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
<div class="block block-flexible block-<?php echo htmlspecialchars($name); ?>">
    <?php foreach ($children as $layout): ?>
        <?php
        $layoutName = $layout['layout'] ?? 'default';
        $layoutChildren = $layout['children'] ?? [];
        ?>
        <div class="flexible-layout flexible-layout-<?php echo htmlspecialchars($layoutName); ?>">
            <?php echo function_exists('render_blocks') ? render_blocks($layoutChildren, $theme_path) : ''; ?>
        </div>
    <?php endforeach; ?>
</div>
