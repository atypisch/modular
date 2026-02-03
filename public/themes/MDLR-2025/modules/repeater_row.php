<?php
/**
 * Repeater row: wrapper for row children. Rendered by repeater.php; this is a fallback.
 */
if (!isset($block)) {
    return;
}
$children = $block['children'] ?? [];
if (!function_exists('render_blocks') && defined('MODULAR_PATH')) {
    require_once MODULAR_PATH . '/core/functions/blocks.php';
}
$theme_path = defined('THEMES_PATH') && defined('DEFAULT_THEME') ? THEMES_PATH . '/' . DEFAULT_THEME : '';
?>
<div class="block block-repeater-row">
    <?php echo function_exists('render_blocks') ? render_blocks($children, $theme_path) : ''; ?>
</div>
