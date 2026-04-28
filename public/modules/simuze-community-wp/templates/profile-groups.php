<?php
/**
 * User Profile Groups Template with Create Group functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user_auth = $auth->get_current_user();
$is_own_profile = $current_user_auth && $current_user_auth->id === $user->id;

// Determine the base URL for navigation links (same as in profile.php)
if (isset($_GET['user']) && !empty($_GET['user'])) {
    $profile_base_url = home_url('/profile/?user=' . urlencode($_GET['user']));
} elseif ($is_own_profile) {
    $profile_base_url = home_url('/profile/');
} else {
    $profile_base_url = $user->get_url();
}

// Get user's groups
$user_groups = array();
$following_groups = array();
$invitations = array();

if (class_exists('Simuze_Group')) {
    try {
        $user_groups = Simuze_Group::get_user_groups($user->id, 50);
        if (!is_array($user_groups)) {
            $user_groups = array();
        }
    } catch (Exception $e) {
        error_log('SIMUZE ERROR: Failed to get user groups: ' . $e->getMessage());
        $user_groups = array();
    }
    
    try {
        $following_groups = Simuze_Group::get_following_groups($user->id, 50);
        if (!is_array($following_groups)) {
            $following_groups = array();
        }
    } catch (Exception $e) {
        error_log('SIMUZE ERROR: Failed to get following groups: ' . $e->getMessage());
        $following_groups = array();
    }
    
    // Get invitations (only for own profile)
    if ($is_own_profile) {
        try {
            $invitations = Simuze_Group::get_user_invitations($user->id);
            if (!is_array($invitations)) {
                $invitations = array();
            }
        } catch (Exception $e) {
            error_log('SIMUZE ERROR: Failed to get invitations: ' . $e->getMessage());
            $invitations = array();
        }
    }
}

// Get group types for the create form
$group_types = array();
if (class_exists('Simuze_Group')) {
    try {
        $group_types = Simuze_Group::get_group_types();
        if (!is_array($group_types)) {
            $group_types = array();
        }
    } catch (Exception $e) {
        error_log('SIMUZE ERROR: Failed to get group types: ' . $e->getMessage());
        $group_types = array();
    }
}

// Tab handling
$groups_tab = isset($_GET['groups_tab']) ? sanitize_text_field($_GET['groups_tab']) : 'member';
?>

<div class="profile-groups-page">
    <!-- Header with Create Button -->
    <div class="page-header">
        <h2>Groups</h2>
        <?php if ($is_own_profile): ?>
            <a href="<?php echo esc_url(add_query_arg('tab', 'create-group', $user->get_url())); ?>" class="button button-primary">
                <i class="fas fa-plus"></i> Create Group
            </a>
        <?php endif; ?>
    </div>

    <!-- Groups Tabs -->
    <div class="groups-tabs">
        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'groups', 'groups_tab' => 'member'), $profile_base_url)); ?>" 
           class="<?php echo $groups_tab === 'member' ? 'active' : ''; ?>">
            My Groups (<?php echo count($user_groups); ?>)
        </a>
        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'groups', 'groups_tab' => 'following'), $profile_base_url)); ?>" 
           class="<?php echo $groups_tab === 'following' ? 'active' : ''; ?>">
            Following (<?php echo count($following_groups); ?>)
        </a>
        <?php if ($is_own_profile && !empty($invitations)): ?>
        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'groups', 'groups_tab' => 'invites'), $profile_base_url)); ?>" 
           class="<?php echo $groups_tab === 'invites' ? 'active' : ''; ?>">
            Invitations (<?php echo count($invitations); ?>)
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Groups Content -->
    <div class="groups-content">
        <?php if ($groups_tab === 'member'): ?>
            <?php if (!empty($user_groups)): ?>
                <div class="groups-grid">
                    <?php foreach ($user_groups as $group_data): ?>
                        <?php 
                        // Handle both array and object data
                        if (is_array($group_data)) {
                            $group_obj = new Simuze_Group($group_data);
                            $role = isset($group_data['member_role']) ? $group_data['member_role'] : (isset($group_data['role']) ? $group_data['role'] : 'member');
                        } else {
                            $group_obj = $group_data;
                            $role = 'member';
                        }
                        ?>
                        <div class="group-card">
                            <a href="<?php echo esc_url($group_obj->get_url()); ?>" title="<?php echo esc_attr($group_obj->name); ?>">
                                <img src="<?php echo esc_url($group_obj->get_avatar_url('thumb')); ?>" 
                                     alt="<?php echo esc_attr($group_obj->name); ?>" 
                                     class="group-avatar"
                                     onerror="this.src='<?php echo esc_url(get_template_directory_uri() . '/images/default-group.svg'); ?>';">
                                <div class="group-info">
                                    <h3 class="group-name" title="<?php echo esc_attr($group_obj->name); ?>"><?php echo esc_html($group_obj->name); ?></h3>
                                    <span class="group-members"><?php echo esc_html($group_obj->get_member_count()); ?> members</span>
                                    <span class="group-role badge"><?php echo esc_html(ucfirst($role)); ?></span>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No groups yet</h3>
                    <p>Create your first group or explore existing groups to join.</p>
                    <?php if ($is_own_profile): ?>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'create-group', $user->get_url())); ?>" class="button button-primary">
                            <i class="fas fa-plus"></i> Create Your First Group
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php elseif ($groups_tab === 'following'): ?>
            <?php if (!empty($following_groups)): ?>
                <div class="groups-grid">
                    <?php foreach ($following_groups as $group_data): ?>
                        <?php 
                        // Handle both array and object data
                        if (is_array($group_data)) {
                            $group_obj = new Simuze_Group($group_data);
                        } else {
                            $group_obj = $group_data;
                        }
                        ?>
                        <div class="group-card">
                            <a href="<?php echo esc_url($group_obj->get_url()); ?>" title="<?php echo esc_attr($group_obj->name); ?>">
                                <img src="<?php echo esc_url($group_obj->get_avatar_url('thumb')); ?>" 
                                     alt="<?php echo esc_attr($group_obj->name); ?>" 
                                     class="group-avatar">
                                <div class="group-info">
                                    <h3 class="group-name" title="<?php echo esc_attr($group_obj->name); ?>"><?php echo esc_html($group_obj->name); ?></h3>
                                    <span class="group-members"><?php echo esc_html($group_obj->get_member_count()); ?> members</span>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-heart"></i>
                    <h3>Not following any groups</h3>
                    <p>Follow groups to stay updated with their activity.</p>
                    <a href="<?php echo esc_url(home_url('/groups/')); ?>" class="button">Explore Groups</a>
                </div>
            <?php endif; ?>
            
        <?php elseif ($groups_tab === 'invites' && $is_own_profile): ?>
            <?php if (!empty($invitations)): ?>
                <div class="invitations-list">
                    <?php foreach ($invitations as $invitation): ?>
                        <?php $group_obj = new Simuze_Group($invitation); ?>
                        <div class="invitation-card" data-invitation-id="<?php echo esc_attr($invitation['invitation_id']); ?>">
                            <div class="invitation-group">
                                <img src="<?php echo esc_url($group_obj->avatar_url ?: get_template_directory_uri() . '/images/default-group.png'); ?>" 
                                     alt="<?php echo esc_attr($group_obj->name); ?>" 
                                     class="group-avatar">
                                <div class="group-info">
                                    <h3><?php echo esc_html($group_obj->name); ?></h3>
                                    <?php if (!empty($invitation['message'])): ?>
                                        <p class="invite-message">"<?php echo esc_html($invitation['message']); ?>"</p>
                                    <?php endif; ?>
                                    <span class="invite-date">Invited <?php echo esc_html(human_time_diff(strtotime($invitation['invited_at']))); ?> ago</span>
                                </div>
                            </div>
                            <div class="invitation-actions">
                                <button class="button button-primary accept-invite" data-group-id="<?php echo esc_attr($group_obj->id); ?>">Accept</button>
                                <button class="button decline-invite" data-group-id="<?php echo esc_attr($group_obj->id); ?>">Decline</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-envelope-open"></i>
                    <h3>No pending invitations</h3>
                    <p>You'll see group invitations here when someone invites you.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

