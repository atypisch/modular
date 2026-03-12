<?php
/**
 * Public Group Profile Template
 * Public view of group profile - visible to everyone
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_owner = $current_user && ($group->creator_id === $current_user->id);
$is_member = $current_user && ($group->is_member($current_user->id) || $is_owner);
$is_admin = $current_user && $group->can_manage($current_user->id);

// Determine group type (artist, label, country, etc.)
$group_type_data = $group->get_group_type();
$group_type_slug = $group_type_data ? $group_type_data['slug'] : null;

// Get public stats
$member_count = $group->get_member_count();
$followers_count = class_exists('Simuze_Follow') ? Simuze_Follow::count_followers($group->id, 'group') : 0;

// Get albums and tracks count — modular tables only (no WP posts)
$albums_count = md_db_var(
    "SELECT COUNT(*) FROM md_music_albums WHERE group_id = ? AND status = 'published' AND deleted_at IS NULL",
    [$group->id]
);

$tracks_count = md_db_var(
    "SELECT COUNT(*) FROM md_music_uploads WHERE group_id = ? AND status = 'published'",
    [$group->id]
);

// Get recent members (public)
$recent_members = md_db_query(
    "SELECT u.*, gm.role
    FROM simuze_group_members gm
    INNER JOIN simuze_users u ON gm.user_id = u.id
    WHERE gm.group_id = ? AND gm.status = 'active'
    ORDER BY gm.joined_at DESC
    LIMIT 6",
    [$group->id]
);

// Auto-derive genres for artist groups based on uploaded music
$derived_genres = array();
if ($group_type_slug === 'artist' && function_exists('simuze_get_artist_genres_from_uploads')) {
    $derived_genres = simuze_get_artist_genres_from_uploads($group->id, 20);
}
?>

<div class="public-profile group-profile">
    
    <!-- Group Info (without header/avatar - those are shown in group.php) -->
    <div class="group-info-public">
        <div class="group-details-public">
            <?php if (!empty($group->description)): ?>
                <p class="group-description"><?php echo esc_html($group->description); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($derived_genres)): ?>
                <div class="group-genres-public">
                    <h3 class="group-genres-title">Genres</h3>
                    <div class="group-genres-tags">
                        <?php foreach ($derived_genres as $genre): ?>
                            <span class="group-genre-tag">
                                <?php echo esc_html($genre); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Public Stats -->
            <div class="public-stats">
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($member_count); ?></span>
                    <span class="stat-label">Members</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($followers_count); ?></span>
                    <span class="stat-label">Followers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($albums_count); ?></span>
                    <span class="stat-label">Albums</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($tracks_count); ?></span>
                    <span class="stat-label">Tracks</span>
                </div>
            </div>
            
            <!-- Actions -->
            <?php if ($current_user): ?>
                <div class="group-actions">
                    <?php if ($is_member): ?>
                        <?php if ($is_admin): ?>
                            <a href="<?php echo esc_url(add_query_arg('tab', 'dashboard', $group->get_url())); ?>" class="btn btn-primary">
                                View Dashboard
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-outline leave-group" data-group-id="<?php echo esc_attr($group->id); ?>">
                            Leave Group
                        </button>
                    <?php else: ?>
                        <?php
                        $is_following = class_exists('Simuze_Follow') ? Simuze_Follow::is_following($current_user->id, 'group', $group->id) : false;
                        ?>
                        <button class="btn btn-primary join-group" data-group-id="<?php echo esc_attr($group->id); ?>">
                            Join Group
                        </button>
                        <button class="btn btn-outline <?php echo $is_following ? 'unfollow-group' : 'follow-group'; ?>" 
                                data-group-id="<?php echo esc_attr($group->id); ?>"
                                data-action="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                            <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Members -->
    <?php if (!empty($recent_members)): ?>
    <div class="profile-section">
        <h2>Members</h2>
        <div class="members-grid-public">
            <?php foreach ($recent_members as $member): 
                $member_user = new Simuze_User($member);
            ?>
                <a href="<?php echo esc_url($member_user->get_url()); ?>" class="member-card-public">
                    <?php
                    // Get avatar URL using SIMEZU fetch method
                    $member_avatar_url = '';
                    $member_simezu_id = $member_user->simezu_id ?? '';
                    if (!empty($member_simezu_id) && class_exists('Simuze_Avatar')) {
                        $avatar = Simuze_Avatar::get_instance();
                        $member_avatar_url = $avatar->get_user_avatar_url($member_simezu_id, 'thumb', array('cache_only' => false));
                        
                        // Fallback: if avatar_url is default/placeholder, go via Modular Simezu auth proxy
                        if (empty($member_avatar_url) || strpos($member_avatar_url, 'default-avatar') !== false || strpos($member_avatar_url, 'data:image') !== false) {
                            $api_url = rtrim(md_home_url('/api/auth'), '/');
                            $member_avatar_url = $api_url . '/users/' . urlencode($member_simezu_id) . '/avatar/image?size=64';
                        }
                    } else {
                        $member_avatar_url = $member_user->get_avatar_url('thumb', array('cache_only' => false));
                    }
                    if (empty($member_avatar_url)) {
                        $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                        $member_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : md_asset_url('images/profile_default.jpg');
                    }
                    ?>
                    <img src="<?php echo esc_url($member_avatar_url); ?>" 
                         alt="<?php echo esc_attr($member_user->display_name); ?>" 
                         class="member-avatar user-avatar"
                         <?php if (!empty($member_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($member_simezu_id); ?>"<?php endif; ?>
                         onerror="this.src='<?php echo esc_url(theme_uri('/images/profile_default.jpg')); ?>';">
                    <div class="member-info">
                        <h3 class="member-name" title="<?php echo esc_attr($member_user->display_name); ?>"><?php echo esc_html($member_user->display_name); ?></h3>
                        <span class="member-role badge"><?php echo esc_html(ucfirst($member['role'] ?? 'member')); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="view-all-link">
            <a href="<?php echo esc_url(add_query_arg('tab', 'members', $group->get_url())); ?>">View all members →</a>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<style>
.group-genres-public {
    margin: 20px 0 16px;
}

.group-genres-title {
    margin: 0 0 8px 0;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255,255,255,0.7);
}

.group-genres-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.group-genre-tag {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    background: rgba(168, 213, 186, 0.16);
    border: 1px solid rgba(168, 213, 186, 0.45);
    color: #a8d5ba;
    font-size: 0.8rem;
    font-weight: 500;
}
</style>
