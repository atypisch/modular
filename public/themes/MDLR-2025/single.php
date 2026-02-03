<?php
/**
 * Single post template. Expects $post with keys: id, title, slug, blocks, etc.
 * Load blocks renderer and output title + blocks.
 */
if (!isset($post) || !is_array($post)) {
    return;
}
if (!function_exists('render_blocks') && defined('MODULAR_PATH')) {
    require_once MODULAR_PATH . '/core/functions/blocks.php';
}
$blocks = $post['blocks'] ?? [];
$theme_path = __DIR__;
?>
<article class="single-post" data-post-id="<?php echo (int)($post['id'] ?? 0); ?>">
    <header class="post-header">
        <h1 class="post-title"><?php echo htmlspecialchars($post['title'] ?? ''); ?></h1>
    </header>
    <div class="post-content">
        <?php echo function_exists('render_blocks') ? render_blocks($blocks, $theme_path) : ''; ?>
    </div>
</article>
