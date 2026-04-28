<?php
/**
 * Products Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . SIMUZE_DB_PREFIX;

// Tab handling
$tab = isset($_GET['product_tab']) ? sanitize_text_field($_GET['product_tab']) : 'songs';

// Get songs
$songs = $wpdb->get_results(
    "SELECT s.*, g.name as group_name 
     FROM {$prefix}songs s 
     LEFT JOIN {$prefix}groups g ON s.group_id = g.id 
     WHERE s.deleted_at IS NULL 
     ORDER BY s.created_at DESC 
     LIMIT 50",
    ARRAY_A
);

// Get albums
$albums = $wpdb->get_results(
    "SELECT a.*, g.name as group_name,
     (SELECT COUNT(*) FROM {$prefix}album_songs WHERE album_id = a.id) as song_count
     FROM {$prefix}albums a 
     LEFT JOIN {$prefix}groups g ON a.group_id = g.id 
     WHERE a.deleted_at IS NULL 
     ORDER BY a.created_at DESC 
     LIMIT 50",
    ARRAY_A
);
?>

<div class="wrap">
    <h1>Products</h1>
    
    <!-- Tabs -->
    <nav class="nav-tab-wrapper">
        <a href="?page=simuze-products&product_tab=songs" class="nav-tab <?php echo $tab === 'songs' ? 'nav-tab-active' : ''; ?>">
            Songs (<?php echo count($songs); ?>)
        </a>
        <a href="?page=simuze-products&product_tab=albums" class="nav-tab <?php echo $tab === 'albums' ? 'nav-tab-active' : ''; ?>">
            Albums (<?php echo count($albums); ?>)
        </a>
    </nav>
    
    <?php if ($tab === 'songs'): ?>
    <!-- Songs Tab -->
    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Title</th>
                <th>Artist/Group</th>
                <th>Price</th>
                <th>Genre</th>
                <th>Downloads</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($songs)): ?>
                <tr><td colspan="8">No songs found.</td></tr>
            <?php else: ?>
                <?php foreach ($songs as $song): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($song['title']); ?></strong>
                            <br><small><?php echo esc_html($song['slug']); ?></small>
                        </td>
                        <td><?php echo esc_html($song['group_name'] ?: 'Unknown'); ?></td>
                        <td>
                            <?php if ($song['price'] !== null && $song['price'] > 0): ?>
                                €<?php echo number_format($song['price'], 2); ?>
                            <?php else: ?>
                                <span class="free-badge">Free</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($song['genre'] ?: '-'); ?></td>
                        <td><?php echo esc_html($song['download_count']); ?></td>
                        <td>
                            <?php if ($song['is_active'] && $song['is_purchasable']): ?>
                                <span class="status-active">Active</span>
                            <?php elseif ($song['is_active']): ?>
                                <span class="status-preview">Preview Only</span>
                            <?php else: ?>
                                <span class="status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(date('M j, Y', strtotime($song['created_at']))); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=simuze-products&action=edit-song&id=' . $song['id']); ?>" class="button button-small">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php else: ?>
    <!-- Albums Tab -->
    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Cover</th>
                <th>Title</th>
                <th>Artist/Group</th>
                <th>Songs</th>
                <th>Price</th>
                <th>Downloads</th>
                <th>Release Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($albums)): ?>
                <tr><td colspan="9">No albums found.</td></tr>
            <?php else: ?>
                <?php foreach ($albums as $album): ?>
                    <tr>
                        <td style="width: 60px;">
                            <?php if ($album['cover_image']): ?>
                                <img src="<?php echo esc_url($album['cover_image']); ?>" width="50" height="50" style="object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #f0f0f1; border-radius: 4px;"></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($album['title']); ?></strong>
                            <br><small><?php echo esc_html($album['slug']); ?></small>
                        </td>
                        <td><?php echo esc_html($album['group_name'] ?: 'Unknown'); ?></td>
                        <td><?php echo esc_html($album['song_count']); ?> songs</td>
                        <td>
                            <?php if ($album['price'] !== null && $album['price'] > 0): ?>
                                €<?php echo number_format($album['price'], 2); ?>
                            <?php else: ?>
                                <span class="calculated">Calculated</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($album['download_count']); ?></td>
                        <td><?php echo $album['release_date'] ? esc_html(date('M j, Y', strtotime($album['release_date']))) : '-'; ?></td>
                        <td>
                            <?php if ($album['is_active']): ?>
                                <span class="status-active">Active</span>
                            <?php else: ?>
                                <span class="status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=simuze-products&action=edit-album&id=' . $album['id']); ?>" class="button button-small">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<style>
.free-badge {
    background: #d4edda;
    color: #155724;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
}

.calculated {
    color: #646970;
    font-style: italic;
}

.status-active {
    color: #46b450;
    font-weight: 500;
}

.status-inactive {
    color: #dc3232;
}

.status-preview {
    color: #0073aa;
}
</style>











