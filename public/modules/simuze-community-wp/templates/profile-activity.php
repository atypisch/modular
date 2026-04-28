<?php
/**
 * User Profile Activity Template
 * Full width layout with simple textarea editor
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_own_profile = $current_user && $current_user->id === $user->id;

// Get activity/posts
$page = isset($_GET['activity_page']) ? max(1, intval($_GET['activity_page'])) : 1;
$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'posts';

// Get user's posts
$posts = Simuze_Activity::get_user_posts($user->id, array(
    'per_page' => 10,
    'page' => $page,
    'viewer_id' => $current_user ? $current_user->id : null,
));

// Get activity feed (includes followed users/groups)
$feed = array();
if ($is_own_profile && $view === 'feed') {
    $feed = Simuze_Activity::get_user_feed($user->id, array(
        'per_page' => 20,
        'page' => $page,
    ));
}
?>

<div class="activity-page-full">
    <div class="activity-header-full">
        <h2>Activity</h2>
        
        <?php if ($is_own_profile): ?>
        <div class="activity-tabs-full">
            <a href="<?php echo esc_url(add_query_arg(array('tab' => 'activity', 'view' => 'posts'), $user->get_url())); ?>" 
               class="tab <?php echo $view === 'posts' ? 'active' : ''; ?>">
                My Posts
            </a>
            <a href="<?php echo esc_url(add_query_arg(array('tab' => 'activity', 'view' => 'feed'), $user->get_url())); ?>" 
               class="tab <?php echo $view === 'feed' ? 'active' : ''; ?>">
                Feed
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($is_own_profile && $view === 'posts'): ?>
    <!-- Create Post Form -->
    <div class="create-post-card-full">
        <form id="create-post-form" class="post-form">
            <?php wp_nonce_field('simuze_create_post', 'post_nonce'); ?>
            
            <div class="post-form-header-full">
                <?php
                // Get avatar URL using SIMEZU fetch method
                $user_avatar_url = '';
                $user_simezu_id = $user->simezu_id ?? '';
                if (!empty($user_simezu_id) && class_exists('Simuze_Avatar')) {
                    $avatar = Simuze_Avatar::get_instance();
                    $user_avatar_url = $avatar->get_user_avatar_url($user_simezu_id, 'thumb', array('cache_only' => false));
                    
                    // Fallback: if avatar_url is default/placeholder, use direct Simezu endpoint
                    if (empty($user_avatar_url) || strpos($user_avatar_url, 'default-avatar') !== false || strpos($user_avatar_url, 'data:image') !== false) {
                        $api_url = get_option('simuze_auth_api_url', 'http://localhost/simezu/api.php/auth');
                        $api_url = str_replace('/api.php/auth', '', $api_url);
                        $user_avatar_url = $api_url . '/api/auth/users/' . urlencode($user_simezu_id) . '/avatar/image?size=40';
                    }
                } else {
                    $user_avatar_url = $user->get_avatar_url(40, array('cache_only' => false));
                }
                if (empty($user_avatar_url)) {
                    $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                    $user_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : get_template_directory_uri() . '/images/profile_default.jpg';
                }
                ?>
                <img src="<?php echo esc_url($user_avatar_url); ?>" 
                     alt="" 
                     class="avatar-small"
                     <?php if (!empty($user_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($user_simezu_id); ?>"<?php endif; ?>>
                <span>What's on your mind?</span>
            </div>
            
            <div class="post-editor-container-full">
                <textarea id="post-content-editor" name="content" class="post-content-textarea" placeholder="Share an update..."></textarea>
            </div>
            
            <!-- Media Embed Fields -->
            <div class="post-media-options-full">
                <button type="button" class="media-btn" id="add-image-btn" title="Add Image">
                    <i class="fas fa-image"></i> Image
                </button>
                <button type="button" class="media-btn" id="add-video-btn" title="Add Video">
                    <i class="fas fa-video"></i> Video
                </button>
            </div>
            
            <div id="media-preview" class="media-preview-full" style="display: none;">
                <div class="media-item-full" id="image-preview" style="display: none;">
                    <img src="" alt="Preview" id="image-preview-img">
                    <button type="button" class="remove-media" data-type="image">&times;</button>
                </div>
                <div class="media-item-full" id="video-preview" style="display: none;">
                    <div class="video-embed" id="video-embed-preview"></div>
                    <button type="button" class="remove-media" data-type="video">&times;</button>
                </div>
            </div>
            
            <input type="hidden" name="image_url" id="post-image-url">
            <input type="hidden" name="video_url" id="post-video-url">
            
            <div class="post-form-footer-full">
                <div class="post-options">
                    <select name="visibility" id="post-visibility">
                        <option value="public">🌍 Public</option>
                        <option value="friends">👥 Friends only</option>
                        <option value="private">🔒 Only me</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" id="post-submit">
                    <i class="fas fa-paper-plane"></i> Post
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- Posts/Feed List -->
    <div class="activity-list-full">
        <?php 
        $items = ($view === 'feed' && $is_own_profile) ? $feed : $posts;
        
        if (empty($items)): 
        ?>
            <div class="empty-state-full">
                <i class="fas fa-stream"></i>
                <h3>No activity yet</h3>
                <?php if ($is_own_profile): ?>
                    <p>Share your first update above!</p>
                <?php else: ?>
                    <p><?php echo esc_html($user->display_name); ?> hasn't posted anything yet.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): 
                $is_post = isset($item['meta']['is_post']) && $item['meta']['is_post'];
                $author = Simuze_User::get($item['user_id']);
                if (!$author) continue;
                
                $is_own_post = $current_user && $current_user->id == $item['user_id'];
                $is_group_post = !empty($item['group_id']);
                $group = $is_group_post ? Simuze_Group::get($item['group_id']) : null;
                
                $meta = !empty($item['meta']) ? (is_string($item['meta']) ? json_decode($item['meta'], true) : $item['meta']) : array();
                $image_url = isset($meta['image_url']) ? $meta['image_url'] : '';
                $video_url = isset($meta['video_url']) ? $meta['video_url'] : '';
            ?>
                <div class="activity-card-full <?php echo $is_post ? 'post-card' : ''; ?>" data-id="<?php echo esc_attr($item['id']); ?>" data-group="<?php echo $is_group_post ? '1' : '0'; ?>">
                    <div class="activity-card-header-full">
                        <a href="<?php echo esc_url($author->get_url()); ?>" class="author-link">
                            <?php
                            // Get avatar URL using SIMEZU fetch method
                            $author_avatar_url = '';
                            $author_simezu_id = $author->simezu_id ?? '';
                            if (!empty($author_simezu_id) && class_exists('Simuze_Avatar')) {
                                $avatar = Simuze_Avatar::get_instance();
                                $author_avatar_url = $avatar->get_user_avatar_url($author_simezu_id, 'thumb', array('cache_only' => false));
                                
                                // Fallback: if avatar_url is default/placeholder, use direct Simezu endpoint
                                if (empty($author_avatar_url) || strpos($author_avatar_url, 'default-avatar') !== false || strpos($author_avatar_url, 'data:image') !== false) {
                                    $api_url = get_option('simuze_auth_api_url', 'http://localhost/simezu/api.php/auth');
                                    $api_url = str_replace('/api.php/auth', '', $api_url);
                                    $author_avatar_url = $api_url . '/api/auth/users/' . urlencode($author_simezu_id) . '/avatar/image?size=40';
                                }
                            } else {
                                $author_avatar_url = $author->get_avatar_url(40, array('cache_only' => false));
                            }
                            if (empty($author_avatar_url)) {
                                $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                                $author_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : get_template_directory_uri() . '/images/profile_default.jpg';
                            }
                            ?>
                            <img src="<?php echo esc_url($author_avatar_url); ?>" 
                                 alt="" 
                                 class="avatar-small"
                                 <?php if (!empty($author_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($author_simezu_id); ?>"<?php endif; ?>>
                            <div class="author-info">
                                <span class="author-name"><?php echo esc_html($author->display_name); ?></span>
                                <?php if ($group): ?>
                                    <span class="post-context">
                                        posted in <a href="<?php echo esc_url($group->get_url()); ?>"><?php echo esc_html($group->name); ?></a>
                                    </span>
                                <?php endif; ?>
                                <span class="post-time">
                                    <?php echo esc_html(human_time_diff(strtotime($item['created_at']))); ?> ago
                                    <?php if (isset($meta['visibility']) && $meta['visibility'] !== 'public'): ?>
                                        <i class="fas fa-<?php echo $meta['visibility'] === 'friends' ? 'users' : 'lock'; ?>" title="<?php echo ucfirst($meta['visibility']); ?>"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </a>
                        
                        <?php if ($is_own_post): ?>
                        <div class="post-actions-menu">
                            <button type="button" class="btn-menu"><i class="fas fa-ellipsis-h"></i></button>
                            <div class="dropdown-menu">
                                <button type="button" class="edit-post"><i class="fas fa-edit"></i> Edit</button>
                                <button type="button" class="delete-post"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($is_post && !empty($meta['title'])): ?>
                        <h3 class="post-title"><?php echo esc_html($meta['title']); ?></h3>
                    <?php endif; ?>
                    
                    <div class="activity-card-content-full">
                        <?php if ($is_post): ?>
                            <div class="post-content-full"><?php echo wp_kses_post($item['content']); ?></div>
                            
                            <?php if ($image_url): ?>
                                <div class="post-image-full">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="Post image">
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($video_url): ?>
                                <div class="post-video-full">
                                    <?php echo wp_oembed_get($video_url); ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="activity-text"><?php echo esc_html(Simuze_Activity::format_activity($item)); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($is_post): ?>
                    <div class="activity-card-footer-full">
                        <button type="button" class="btn-like" data-id="<?php echo esc_attr($item['id']); ?>">
                            <i class="far fa-heart"></i> <span class="like-count">0</span>
                        </button>
                        <button type="button" class="btn-comment">
                            <i class="far fa-comment"></i> Comment
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if (count($items) >= 10): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?php echo esc_url(add_query_arg('activity_page', $page - 1)); ?>" class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i> Newer
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url(add_query_arg('activity_page', $page + 1)); ?>" class="btn btn-secondary">
                    Older <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Post Modal -->
<div id="edit-post-modal" class="simuze-modal" style="display: none;">
    <div class="simuze-modal-overlay"></div>
    <div class="simuze-modal-content">
        <button type="button" class="simuze-modal-close">&times;</button>
        <h2>Edit Post</h2>
        <form id="edit-post-form">
            <input type="hidden" name="post_id" id="edit-post-id">
            <input type="hidden" name="is_group_post" id="edit-is-group">
            <textarea id="edit-post-editor" class="post-content-textarea"></textarea>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancel-edit">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
