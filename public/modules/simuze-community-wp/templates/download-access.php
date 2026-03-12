<?php
/**
 * Download Access Page Template
 * Shows available download formats after purchase
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables available: $download_token, $product, $formats
$group = $product ? $product->get_group() : null;
$artwork = '';

if ($product) {
    if (isset($product->artwork_url) && $product->artwork_url) {
        $artwork = $product->artwork_url;
    } elseif (isset($product->cover_image) && $product->cover_image) {
        $artwork = $product->cover_image;
    } elseif ($group) {
        $artwork = $group->get_avatar_url('medium');
    }
}
?>

<div class="download-access-page">
    <div class="download-access-container">
        
        <!-- Header -->
        <div class="download-access-header">
            <div class="download-access-artwork">
                <img src="<?php echo esc_url($artwork); ?>" alt="<?php echo esc_attr($product ? $product->title : ''); ?>">
            </div>
            <div class="download-access-info">
                <h1 class="download-access-title"><?php echo esc_html($product ? $product->title : __('Download', 'simuze')); ?></h1>
                <?php if ($group): ?>
                <p class="download-access-artist">
                    <a href="<?php echo esc_url($group->get_url()); ?>"><?php echo esc_html($group->name); ?></a>
                </p>
                <?php endif; ?>
                <p class="download-access-status">
                    <i class="fas fa-check-circle"></i>
                    <?php _e('Purchase confirmed', 'simuze'); ?>
                </p>
            </div>
        </div>
        
        <!-- Format Selection -->
        <div class="download-formats-section">
            <h2><?php _e('Choose your format', 'simuze'); ?></h2>
            <p class="formats-hint"><?php _e('Download in any format you prefer. MP3 320kbps is recommended for most listeners.', 'simuze'); ?></p>
            
            <div class="download-formats-grid">
                <?php foreach ($formats as $format_key => $format): ?>
                <div class="download-format-card<?php echo $format['available'] ? '' : ' unavailable'; ?>">
                    <div class="format-info">
                        <h3 class="format-name"><?php echo esc_html($format['label']); ?></h3>
                        <?php if (isset($format['size'])): ?>
                        <span class="format-size"><?php echo esc_html($format['size']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($format['available']): ?>
                    <a href="<?php echo esc_url(add_query_arg(array(
                        'action' => 'simuze_process_download',
                        'token' => $download_token->token,
                        'format' => $format_key
                    ), admin_url('admin-ajax.php'))); ?>" 
                       class="format-download-btn"
                       download>
                        <i class="fas fa-download"></i>
                        <?php _e('Download', 'simuze'); ?>
                    </a>
                    <?php else: ?>
                    <span class="format-unavailable"><?php _e('Not available', 'simuze'); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Download Info -->
        <div class="download-info-section">
            <div class="download-info-item">
                <i class="fas fa-sync-alt"></i>
                <div>
                    <strong><?php _e('Downloads remaining', 'simuze'); ?></strong>
                    <span>
                        <?php 
                        if ($download_token->max_downloads) {
                            $remaining = max(0, $download_token->max_downloads - $download_token->download_count);
                            echo $remaining . ' / ' . $download_token->max_downloads;
                        } else {
                            _e('Unlimited', 'simuze');
                        }
                        ?>
                    </span>
                </div>
            </div>
            <div class="download-info-item">
                <i class="fas fa-clock"></i>
                <div>
                    <strong><?php _e('Link expires', 'simuze'); ?></strong>
                    <span>
                        <?php 
                        if ($download_token->expires_at) {
                            echo date_i18n(get_option('date_format'), strtotime($download_token->expires_at));
                        } else {
                            _e('Never', 'simuze');
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Back Links -->
        <div class="download-access-footer">
            <?php if ($group): ?>
            <a href="<?php echo esc_url($group->get_url()); ?>" class="back-to-artist">
                <i class="fas fa-arrow-left"></i>
                <?php printf(__('Back to %s', 'simuze'), esc_html($group->name)); ?>
            </a>
            <?php endif; ?>
            
            <?php 
            $auth = Simuze_Auth::get_instance();
            $current_user = $auth->get_current_user();
            if ($current_user): 
            ?>
            <a href="<?php echo esc_url(home_url('/profile/?tab=purchases')); ?>" class="view-all-downloads">
                <?php _e('View all purchases', 'simuze'); ?>
                <i class="fas fa-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.download-access-page {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
}

.download-access-container {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 40px;
    max-width: 600px;
    width: 100%;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.download-access-header {
    display: flex;
    gap: 24px;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.download-access-artwork img {
    width: 120px;
    height: 120px;
    border-radius: 12px;
    object-fit: cover;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.download-access-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.download-access-title {
    margin: 0 0 8px;
    font-size: 24px;
    color: #fff;
}

.download-access-artist a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
}

.download-access-artist a:hover {
    color: #51ac32;
}

.download-access-status {
    margin-top: auto;
    color: #51ac32;
    font-size: 14px;
}

.download-access-status i {
    margin-right: 6px;
}

.download-formats-section h2 {
    font-size: 18px;
    margin: 0 0 8px;
    color: #fff;
}

.formats-hint {
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
    margin: 0 0 20px;
}

.download-formats-grid {
    display: grid;
    gap: 12px;
}

.download-format-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.2s ease;
}

.download-format-card:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.2);
}

.download-format-card.unavailable {
    opacity: 0.5;
}

.format-name {
    margin: 0;
    font-size: 16px;
    color: #fff;
}

.format-size {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.5);
}

.format-download-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #51ac32;
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
}

.format-download-btn:hover {
    background: #45a028;
    transform: translateY(-1px);
    color: #fff;
}

.format-unavailable {
    color: rgba(255, 255, 255, 0.4);
    font-size: 13px;
}

.download-info-section {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.download-info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    color: rgba(255, 255, 255, 0.7);
}

.download-info-item i {
    font-size: 18px;
    opacity: 0.6;
    margin-top: 2px;
}

.download-info-item strong {
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.7;
    margin-bottom: 4px;
}

.download-info-item span {
    font-size: 14px;
    color: #fff;
}

.download-access-footer {
    margin-top: 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.back-to-artist,
.view-all-downloads {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: color 0.2s;
}

.back-to-artist:hover,
.view-all-downloads:hover {
    color: #51ac32;
}

@media (max-width: 600px) {
    .download-access-container {
        padding: 24px;
    }
    
    .download-access-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .download-info-section {
        grid-template-columns: 1fr;
    }
    
    .download-access-footer {
        flex-direction: column;
        gap: 16px;
    }
}
</style>

