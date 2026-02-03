<?php
/**
 * Relationship block. $block['data']['ids'] and optionally $block['data']['posts'] (resolved).
 */
if (!isset($block)) {
    return;
}
$posts = $block['data']['posts'] ?? [];
$name = $block['name'] ?? '';
if (empty($posts)) {
    return;
}
?>
<div class="block block-relationship block-<?php echo htmlspecialchars($name); ?>">
    <ul class="related-posts">
        <?php foreach ($posts as $p): ?>
            <li><a href="<?php echo htmlspecialchars($p['slug'] ?? '#'); ?>"><?php echo htmlspecialchars($p['title'] ?? ''); ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>
