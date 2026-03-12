<?php
/**
 * Group Dashboard Template
 * Playful widget-based layout - OWNER/ADMIN ONLY
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_member = $current_user && $group->is_member($current_user->id);
$is_admin = $current_user && $group->can_manage($current_user->id);

// Always show dashboard - show full dashboard for members/admins, public stats for non-members
// Non-members will see public stats but can still access all tabs
// Note: Admin-only widgets (unread messages, pending invites, quick actions) are conditionally shown

// Get group stats
$member_count = $group->get_member_count();
$followers_count = class_exists('Simuze_Follow') ? Simuze_Follow::count_followers($group->id, 'group') : 0;

// Get albums and tracks count
global $wpdb;
$albums_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type = 'album' 
    AND p.post_status = 'publish'
    AND pm.meta_key = 'group_id' 
    AND pm.meta_value = %d",
    $group->id
));

// Count tracks for this group
// Tracks can be linked via:
// 1. Direct post meta _simuze_group_id
// 2. Via wp_music_uploads table (upload has group_id, track post has _simuze_upload_id)
$tracks_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT p.ID) 
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm_group ON p.ID = pm_group.post_id AND pm_group.meta_key = '_simuze_group_id'
    LEFT JOIN {$wpdb->postmeta} pm_upload ON p.ID = pm_upload.post_id AND pm_upload.meta_key = '_simuze_upload_id'
    LEFT JOIN {$wpdb->prefix}music_uploads mu ON mu.id = pm_upload.meta_value
    WHERE p.post_type = 'track' 
    AND p.post_status = 'publish'
    AND (
        (pm_group.meta_value = %d)
        OR (mu.group_id = %d)
    )",
    $group->id,
    $group->id
));

// Get recent group activity
$recent_activity = array();
if (class_exists('Simuze_Activity')) {
    global $wpdb;
    $activity_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_activity';
    $recent_activity = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $activity_table 
        WHERE group_id = %d 
        ORDER BY created_at DESC 
        LIMIT 8",
        $group->id
    ), ARRAY_A);
}

// Get activity statistics for groups
$activity_stats = array(
    'recent_posts' => 0,
    'recent_comments' => 0,
    'member_activity' => 0,
);

// Count recent posts (last 7 days)
$activity_stats['recent_posts'] = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE (p.post_type = 'album' OR p.post_type = 'track')
    AND p.post_status = 'publish'
    AND pm.meta_key = 'group_id' 
    AND pm.meta_value = %d
    AND p.post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    $group->id
));

// Count recent activity items (last 7 days)
if (class_exists('Simuze_Activity')) {
    $activity_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_activity';
    $activity_stats['member_activity'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $activity_table 
        WHERE group_id = %d 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
        $group->id
    ));
}

// Get recent members
$recent_members = array();
$members_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_members';
$users_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
$recent_members = $wpdb->get_results($wpdb->prepare(
    "SELECT u.*, gm.role, gm.joined_at 
    FROM $members_table gm
    INNER JOIN $users_table u ON gm.user_id = u.id
    WHERE gm.group_id = %d AND gm.status = 'active'
    ORDER BY gm.joined_at DESC
    LIMIT 6",
    $group->id
), ARRAY_A);

// Get pending invitations (admin only)
$pending_invitations = 0;
if ($is_admin) {
    $invitations_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_invitations';
    $pending_invitations = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $invitations_table 
        WHERE group_id = %d AND status = 'pending'",
        $group->id
    ));
}

// Get unread messages (admin only)
$unread_messages = 0;
if ($is_admin && class_exists('Simuze_Message')) {
    $unread_messages = Simuze_Message::count_unread_for_group($group->id);
}
?>

<div class="playful-dashboard group-dashboard">
    
    <!-- Welcome Header -->
    <div class="dashboard-header">
        <h1><?php echo esc_html($group->name); ?> Dashboard</h1>
        <p class="dashboard-subtitle"><?php echo $is_admin ? 'Manage your group and see what\'s happening' : 'Welcome to the group!'; ?></p>
    </div>
    
    <!-- Widget Grid -->
    <div class="widget-grid">
        
        <!-- Row 1: Unread Messages (1 col), Members (1 col), Pending Invites (2 cols if admin) -->
        <?php if ($is_admin && $unread_messages > 0): ?>
        <div class="widget widget-stat widget-col-3" data-link="<?php echo esc_url(add_query_arg('tab', 'messages', $group->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($unread_messages); ?></div>
                <div class="widget-label">Unread Messages</div>
            </div>
            <div class="widget-badge"><?php echo $unread_messages; ?></div>
        </div>
        <?php endif; ?>
        
        <div class="widget widget-stat widget-col-3" data-link="<?php echo esc_url(add_query_arg('tab', 'members', $group->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($member_count); ?></div>
                <div class="widget-label">Members</div>
            </div>
        </div>
        
        <?php if ($is_admin && $pending_invitations > 0): ?>
        <div class="widget widget-stat widget-col-6" data-link="<?php echo esc_url(add_query_arg('tab', 'invites', $group->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($pending_invitations); ?></div>
                <div class="widget-label">Pending Invites</div>
            </div>
            <div class="widget-badge"><?php echo $pending_invitations; ?></div>
        </div>
        <?php endif; ?>
        
        <!-- Row 2: Followers, Albums, Tracks (3 equal columns) -->
        <div class="widget widget-stat widget-col-4" data-link="<?php echo esc_url(add_query_arg('tab', 'activity', $group->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($followers_count); ?></div>
                <div class="widget-label">Followers</div>
            </div>
        </div>
        
        <div class="widget widget-stat widget-col-4" data-link="<?php echo esc_url(add_query_arg('tab', 'music', $group->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-compact-disc"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($albums_count); ?></div>
                <div class="widget-label">Albums</div>
            </div>
        </div>
        
        <div class="widget widget-stat widget-col-4" data-link="<?php echo esc_url(add_query_arg('tab', 'music', $group->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-music"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($tracks_count); ?></div>
                <div class="widget-label">Tracks</div>
            </div>
        </div>
        
        <!-- Row 3: Activity Metrics -->
        <div class="widget widget-metrics widget-col-6">
            <div class="widget-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="widget-content">
                <div class="widget-label">Activity Statistics</div>
                <div class="metrics-inline">
                    <div class="metric-item">
                        <span class="metric-value"><?php echo number_format($activity_stats['recent_posts']); ?></span>
                        <span class="metric-label">Recent Posts</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-value"><?php echo number_format($activity_stats['member_activity']); ?></span>
                        <span class="metric-label">Member Activity</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Row 3: Activity Feed (full width, no border) -->
        <?php if (!empty($recent_activity)): ?>
        <div class="widget widget-activity widget-full">
            <div class="widget-header">
                <h3>Recent Activity</h3>
                <a href="<?php echo esc_url(add_query_arg('tab', 'activity', $group->get_url())); ?>" class="widget-link">View all →</a>
            </div>
            <div class="activity-feed">
                <?php foreach (array_slice($recent_activity, 0, 6) as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-content">
                            <?php 
                            $user_name = 'Someone';
                            if (!empty($activity['user_id'])) {
                                try {
                                    $user = Simuze_User::get($activity['user_id']);
                                    if ($user) {
                                        $user_name = $user->display_name;
                                    }
                                } catch (Exception $e) {
                                    error_log('SIMUZE ERROR: Failed to get user for activity: ' . $e->getMessage());
                                }
                            }
                            $activity_text = !empty($activity['content']) ? $activity['content'] : (!empty($activity['action']) ? $activity['action'] : 'performed an action');
                            echo esc_html($user_name . ' ' . $activity_text); 
                            ?>
                        </div>
                        <div class="activity-time">
                            <?php 
                            if (!empty($activity['created_at'])) {
                                echo esc_html(human_time_diff(strtotime($activity['created_at']))); 
                            } else {
                                echo 'recently';
                            }
                            ?> ago
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Row 4: Recent Members (Chapter, not widget) -->
        <?php if (!empty($recent_members)): ?>
        <div class="dashboard-chapter">
            <div class="chapter-header">
                <h3>Recent Members</h3>
                <a href="<?php echo esc_url(add_query_arg('tab', 'members', $group->get_url())); ?>" class="chapter-link">View all →</a>
            </div>
            <div class="members-grid">
                <?php foreach ($recent_members as $member): 
                    try {
                        $member_user = new Simuze_User($member);
                        if (!$member_user || !$member_user->id) {
                            continue;
                        }
                    } catch (Exception $e) {
                        error_log('SIMUZE ERROR: Failed to create Simuze_User from member data: ' . $e->getMessage());
                        continue;
                    }
                ?>
                    <a href="<?php echo esc_url($member_user->get_url()); ?>" class="member-card">
                        <?php
                        // Get avatar URL using SIMEZU fetch method
                        $member_avatar_url = '';
                        $member_simezu_id = $member_user->simezu_id ?? '';
                        if (!empty($member_simezu_id) && class_exists('Simuze_Avatar')) {
                            $avatar = Simuze_Avatar::get_instance();
                            $member_avatar_url = $avatar->get_user_avatar_url($member_simezu_id, 'thumb', array('cache_only' => false));
                            
                            // Fallback: if avatar_url is default/placeholder, use direct Simezu endpoint
                            if (empty($member_avatar_url) || strpos($member_avatar_url, 'default-avatar') !== false || strpos($member_avatar_url, 'data:image') !== false) {
                                $api_url = get_option('simuze_auth_api_url', 'http://localhost/simezu/api.php/auth');
                                $api_url = str_replace('/api.php/auth', '', $api_url);
                                $member_avatar_url = $api_url . '/api/auth/users/' . urlencode($member_simezu_id) . '/avatar/image?size=64';
                            }
                        } else {
                            $member_avatar_url = $member_user->get_avatar_url('thumb', array('cache_only' => false));
                        }
                        if (empty($member_avatar_url)) {
                            $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                            $member_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : get_template_directory_uri() . '/images/profile_default.jpg';
                        }
                        ?>
                        <img src="<?php echo esc_url($member_avatar_url); ?>" 
                             alt="<?php echo esc_attr($member_user->display_name); ?>" 
                             class="member-avatar user-avatar"
                             <?php if (!empty($member_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($member_simezu_id); ?>"<?php endif; ?>
                             onerror="this.src='<?php echo esc_url(get_template_directory_uri() . '/images/profile_default.jpg'); ?>';">
                        <div class="member-info">
                            <h4 class="member-name" title="<?php echo esc_attr($member_user->display_name); ?>"><?php echo esc_html($member_user->display_name); ?></h4>
                            <span class="member-role badge"><?php echo esc_html(ucfirst($member['role'] ?? 'member')); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Row 5: Quick Actions (Chapter, not widget) -->
        <?php if ($is_admin): ?>
        <div class="dashboard-chapter">
            <div class="chapter-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="actions-grid">
                <a href="<?php echo esc_url(add_query_arg('tab', 'music', add_query_arg('subtab', 'upload', $group->get_url()))); ?>" class="action-card">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Upload Music</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'invites', $group->get_url())); ?>" class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <span>Invite Members</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'settings', $group->get_url())); ?>" class="action-card">
                    <i class="fas fa-cog"></i>
                    <span>Group Settings</span>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'members', $group->get_url())); ?>" class="action-card">
                    <i class="fas fa-users"></i>
                    <span>Manage Members</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>
