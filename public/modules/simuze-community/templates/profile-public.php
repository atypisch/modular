<?php
/**
 * Public User Profile Template
 * Public view of user profile - visible to everyone
 */

if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_own_profile = $current_user && $current_user->id === $user->id;

// Get public stats
$stats = array(
    'groups' => Simuze_Group::count_user_groups($user->id),
    'friends' => class_exists('Simuze_Friend') ? Simuze_Friend::count_friends($user->id) : 0,
    'followers' => class_exists('Simuze_Follow') ? Simuze_Follow::count_followers($user->id) : 0,
    'following' => class_exists('Simuze_Follow') ? Simuze_Follow::count_following($user->id) : 0,
);

// Get user's groups (public)
$user_groups = Simuze_Group::get_user_groups($user->id, 6);

// Get recent public activity
$recent_activity = class_exists('Simuze_Activity') ? Simuze_Activity::get_user_activity($user->id, 5) : array();

// Get upcoming shows organised by this user
$profile_user_shows = class_exists('EventRepository') ? EventRepository::findByOrganiser((int) $user->id, 'user', 8) : [];
$public_websites = $user->profile['websites'] ?? [];
if (!is_array($public_websites)) {
    $public_websites = !empty($public_websites) ? [$public_websites] : [];
}
?>

<div class="public-profile user-profile">
    <div class="public-profile-grid">
        <div class="public-profile-main">
            <!-- Recent Activity (left column) -->
            <?php if (!empty($recent_activity)): ?>
                <div class="profile-section">
                    <h2>Recent Activity</h2>
                    <div class="activity-list-public">
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item-public">
                                <div class="activity-content">
                                    <?php echo wp_kses_post($activity['content'] ?? Simuze_Activity::format_activity($activity)); ?>
                                </div>
                                <div class="activity-time">
                                    <?php echo esc_html(human_time_diff(strtotime($activity['created_at']))); ?> ago
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="profile-section">
                    <h2>Recent Activity</h2>
                    <p>This user has no public activity yet.</p>
                </div>
            <?php endif; ?>
            <?php if (!empty($profile_user_shows)): ?>
                <div class="profile-section">
                    <h2>Shows</h2>
                    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:.5rem;">
                        <?php foreach ($profile_user_shows as $pshow):
                            $pshow_date = !empty($pshow['event_date']) ? date('j M Y', strtotime($pshow['event_date'])) : '';
                            $pshow_url  = md_home_url('/show/' . $pshow['slug']);
                        ?>
                            <li style="display:flex; align-items:center; gap:.75rem; padding:.5rem 0; border-bottom:1px solid var(--color-border,#e5e7eb);">
                                <div style="flex:1; min-width:0;">
                                    <a href="<?php echo esc_url($pshow_url); ?>" style="font-weight:500; text-decoration:none; color:inherit;"><?php echo esc_html($pshow['title']); ?></a>
                                    <div style="font-size:.8rem; color:var(--color-text-muted,#888);">
                                        <?php echo esc_html($pshow_date); ?>
                                        <?php if (!empty($pshow['city_name'])): ?>&middot; <?php echo esc_html($pshow['city_name']); ?><?php endif; ?>
                                    </div>
                                </div>
                                <a href="<?php echo esc_url($pshow_url); ?>" class="button button-sm">View</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="margin-top:.75rem; margin-bottom:0;"><a href="<?php echo esc_url(md_home_url('/shows')); ?>">Browse all shows &rarr;</a></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="public-profile-sidebar">
            <!-- Public info / About -->
            <div class="profile-section">
                <h2>About</h2>
                <?php if ($user->profile && !empty($user->profile['bio'])): ?>
                    <p class="profile-bio"><?php echo esc_html($user->profile['bio']); ?></p>
                <?php else: ?>
                    <p>This user hasn't added a bio yet.</p>
                <?php endif; ?>
                <?php if (!empty($public_websites)): ?>
                    <div style="margin-top:.75rem; display:flex; flex-direction:column; gap:.45rem;">
                        <?php foreach ($public_websites as $_site): ?>
                            <?php if (is_string($_site) && trim($_site) !== ''): ?>
                                <a href="<?php echo esc_url($_site); ?>" target="_blank" rel="noopener noreferrer"
                                   style="display:inline-flex;align-items:center;gap:6px;color:inherit;text-decoration:none;">
                                    <i class="fas fa-link" aria-hidden="true"></i> <?php echo esc_html($_site); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Public Stats -->
            <div class="profile-section">
                <h2>Stats</h2>
                <div class="public-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($stats['groups']); ?></span>
                        <span class="stat-label">Groups</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($stats['friends']); ?></span>
                        <span class="stat-label">Friends</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($stats['followers']); ?></span>
                        <span class="stat-label">Followers</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($stats['following']); ?></span>
                        <span class="stat-label">Following</span>
                    </div>
                </div>
            </div>
            
            <!-- Groups Section -->
            <?php if (!empty($user_groups)): ?>
            <div class="profile-section">
                <h2>Groups</h2>
                <div class="groups-grid-public">
                    <?php foreach ($user_groups as $group_data): 
                        $group = is_array($group_data) ? Simuze_Group::get($group_data['id']) : $group_data;
                        if (!$group) continue;
                    ?>
                        <a href="<?php echo esc_url($group->get_url()); ?>" class="group-card-public">
                            <img src="<?php echo esc_url($group->get_avatar_url('thumb')); ?>" 
                                 alt="<?php echo esc_attr($group->name); ?>" 
                                 class="group-avatar">
                            <div class="group-info">
                                <h3><?php echo esc_html($group->name); ?></h3>
                                <span class="group-members"><?php echo esc_html($group->get_member_count()); ?> members</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Follow / Unfollow user from public profile
    $(document).on('click', '.btn-follow-user', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var userId = $btn.data('user-id');
        var action = $btn.data('action') || 'follow';
        
        if (!window.simuzeCommunity || !simuzeCommunity.apiUrl) {
            return;
        }
        
        var endpoint = action === 'unfollow' ? 'unfollow' : 'follow';
        var payload = {
            followable_type: 'user',
            followable_id: userId
        };
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: simuzeCommunity.apiUrl + endpoint,
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            }
        }).done(function() {
            if (action === 'follow') {
                $btn
                    .removeClass('btn-primary')
                    .addClass('btn-outline')
                    .text('Following')
                    .data('action', 'unfollow');
            } else {
                $btn
                    .removeClass('btn-outline')
                    .addClass('btn-primary')
                    .text('Follow')
                    .data('action', 'follow');
            }
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });
    
    // Add Friend from public profile
    $(document).on('click', '.btn-add-friend', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var userId = $btn.data('user-id');
        
        if (!window.simuzeCommunity || !simuzeCommunity.apiUrl) {
            return;
        }
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'friends/request',
            type: 'POST',
            data: JSON.stringify({ friend_id: userId }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            }
        }).done(function() {
            $btn
                .removeClass('btn-outline')
                .addClass('btn-primary')
                .text('Request sent');
        }).fail(function(xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not send friend request';
            if (typeof simuzeAlertError === 'function') {
                simuzeAlertError(msg);
            } else {
                alert(msg);
            }
            $btn.prop('disabled', false);
        });
    });
});
</script>

