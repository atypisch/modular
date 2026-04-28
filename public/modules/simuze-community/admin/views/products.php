<?php
/**
 * Products Admin View
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

// Tab handling
$tab = isset($_GET['product_tab']) ? sanitize_text_field($_GET['product_tab']) : 'songs';

// Get tracks (md_music_uploads)
$songs = md_db_query(
    "SELECT s.*, g.name as group_name
     FROM md_music_uploads s
     LEFT JOIN simuze_groups g ON s.group_id = g.id
     WHERE s.status != 'deleted'
     ORDER BY s.created_at DESC
     LIMIT 50"
);

// Get albums (md_music_albums)
$albums = md_db_query(
    "SELECT a.*, g.name as group_name,
     (SELECT COUNT(*) FROM md_music_uploads WHERE album_id = a.id AND status != 'deleted') as song_count
     FROM md_music_albums a
     LEFT JOIN simuze_groups g ON a.group_id = g.id
     WHERE a.deleted_at IS NULL
     ORDER BY a.created_at DESC
     LIMIT 50"
);

$base_url = md_url('/admin/module-page.php?module=simuze-community&page=products');
?>

<div class="wrap">
    <h1>Products</h1>

    <!-- Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url($base_url . '&product_tab=songs'); ?>" class="nav-tab <?php echo $tab === 'songs' ? 'nav-tab-active' : ''; ?>">
            Songs (<?php echo count($songs); ?>)
        </a>
        <a href="<?php echo esc_url($base_url . '&product_tab=albums'); ?>" class="nav-tab <?php echo $tab === 'albums' ? 'nav-tab-active' : ''; ?>">
            Albums (<?php echo count($albums); ?>)
        </a>
    </nav>

    <?php if ($tab === 'songs'): ?>
    <!-- Songs Tab -->
    <table class="wp-list-table widefat fixed striped" style="margin-top:20px;">
        <thead>
            <tr>
                <th>Title</th>
                <th>Artist/Group</th>
                <th>Price</th>
                <th>Genre</th>
                <th>Status</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($songs)): ?>
                <tr><td colspan="6">No songs found.</td></tr>
            <?php else: ?>
                <?php foreach ($songs as $song): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($song['song_name'] ?? $song['title'] ?? '—'); ?></strong>
                        </td>
                        <td><?php echo esc_html($song['group_name'] ?: 'Unknown'); ?></td>
                        <td>
                            <?php if (!empty($song['price']) && $song['price'] > 0): ?>
                                €<?php echo number_format($song['price'], 2); ?>
                            <?php else: ?>
                                <span class="free-badge">Free</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($song['genre'] ?? '-'); ?></td>
                        <td><?php echo esc_html(ucfirst($song['status'] ?? 'published')); ?></td>
                        <td><?php echo esc_html(date('M j, Y', strtotime($song['created_at']))); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php else: ?>
    <!-- Albums Tab -->
    <table class="wp-list-table widefat fixed striped" style="margin-top:20px;">
        <thead>
            <tr>
                <th>Cover</th>
                <th>Title</th>
                <th>Artist/Group</th>
                <th>Songs</th>
                <th>Price</th>
                <th>Release Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($albums)): ?>
                <tr><td colspan="7">No albums found.</td></tr>
            <?php else: ?>
                <?php foreach ($albums as $album): ?>
                    <tr>
                        <td style="width:60px;">
                            <?php if (!empty($album['artwork_path'])): ?>
                                <img src="<?php echo esc_url(md_upload_dir()['baseurl'] . '/' . ltrim($album['artwork_path'], '/')); ?>" width="50" height="50" style="object-fit:cover;border-radius:4px;">
                            <?php else: ?>
                                <div style="width:50px;height:50px;background:#f0f0f1;border-radius:4px;"></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($album['album_name'] ?? $album['title'] ?? '—'); ?></strong>
                        </td>
                        <td><?php echo esc_html($album['group_name'] ?: 'Unknown'); ?></td>
                        <td><?php echo esc_html($album['song_count']); ?> songs</td>
                        <td>
                            <?php if (!empty($album['price']) && $album['price'] > 0): ?>
                                €<?php echo number_format($album['price'], 2); ?>
                            <?php else: ?>
                                <span class="calculated">Calculated</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo !empty($album['release_date']) ? esc_html(date('M j, Y', strtotime($album['release_date']))) : '-'; ?></td>
                        <td><?php echo esc_html(ucfirst($album['status'] ?? 'published')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<style>
.free-badge { background:#d4edda; color:#155724; padding:2px 6px; border-radius:3px; font-size:11px; }
.calculated { color:#646970; font-style:italic; }
</style>
