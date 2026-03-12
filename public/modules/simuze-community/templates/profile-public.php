<?php
/**
 * Public User Profile Template
 * Public view of user profile - visible to everyone
 */

if (!defined('ABSPATH')) {
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

