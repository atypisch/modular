<?php
/**
 * Archive template. Expects $post_type_slug and $posts (list of id, title, slug, published_at, created_at).
 */
$post_type_slug = $post_type_slug ?? '';
$posts = $posts ?? [];
$title = ucfirst($post_type_slug) ?: 'Archive';
?>
<article class="archive archive-<?php echo htmlspecialchars($post_type_slug); ?>">
    <header class="archive-header">
        <h1 class="archive-title"><?php echo htmlspecialchars($title); ?></h1>
    </header>
    <ul class="archive-list">
        <?php foreach ($posts as $p): ?>
            <li>
                <a href="/<?php echo htmlspecialchars($post_type_slug); ?>/<?php echo htmlspecialchars($p['slug'] ?? ''); ?>">
                    <?php echo htmlspecialchars($p['title'] ?? ''); ?>
                </a>
                <?php if (!empty($p['published_at'])): ?>
                    <time datetime="<?php echo htmlspecialchars($p['published_at']); ?>"><?php echo date('Y-m-d', strtotime($p['published_at'])); ?></time>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if (empty($posts)): ?>
        <p>No posts yet.</p>
    <?php endif; ?>
</article>
