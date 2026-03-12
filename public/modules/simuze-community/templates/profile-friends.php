<?php
/**
 * User Profile Friends Template
 * Full width layout matching Playlists style
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure $user is available
if (!isset($user) || !$user) {
    echo '<div class="simuze-error"><p>User not found.</p></div>';
    return;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_own_profile = $current_user && $current_user->id === $user->id;

// Get view/tab
$view = isset($_GET['friends_view']) ? sanitize_text_field($_GET['friends_view']) : 'all';

// Get friends data
$friends = Simuze_Friend::get_friends($user->id);
$pending_requests = $is_own_profile ? Simuze_Friend::get_pending_requests($user->id) : array();
$sent_requests = $is_own_profile ? Simuze_Friend::get_sent_requests($user->id) : array();

// Search functionality
$search = isset($_GET['friend_search']) ? sanitize_text_field($_GET['friend_search']) : '';
if ($search && !empty($friends)) {
    $friends = array_filter($friends, function($friend) use ($search) {
        return stripos($friend['display_name'], $search) !== false || 
               stripos($friend['username'], $search) !== false;
    });
}
?>

<div class="friends-page-full">
    <div class="dashboard-section-header">
        <h3>Friends</h3>
        
        <?php if ($is_own_profile): ?>
        <div class="friends-tabs">
            <a href="<?php echo esc_url(add_query_arg(array('tab' => 'friends', 'friends_view' => 'all'), $user->get_url())); ?>" 
               class="tab-btn-full <?php echo $view === 'all' ? 'active' : ''; ?>">
                All <span class="count"><?php echo count($friends); ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg(array('tab' => 'friends', 'friends_view' => 'requests'), $user->get_url())); ?>" 
               class="tab-btn-full <?php echo $view === 'requests' ? 'active' : ''; ?>">
                Requests 
                <?php if (count($pending_requests) > 0): ?>
                    <span class="count highlight"><?php echo count($pending_requests); ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg(array('tab' => 'friends', 'friends_view' => 'sent'), $user->get_url())); ?>" 
               class="tab-btn-full <?php echo $view === 'sent' ? 'active' : ''; ?>">
                Sent <span class="count"><?php echo count($sent_requests); ?></span>
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-content">
        <?php if ($view === 'all'): ?>
        <!-- All Friends -->
        <?php if ($is_own_profile): ?>
        <div class="friends-search">
            <form method="get" action="">
                <input type="hidden" name="tab" value="friends">
                <input type="hidden" name="friends_view" value="all">
                <div class="search-input-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="friend_search" placeholder="Search friends..." value="<?php echo esc_attr($search); ?>">
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($friends)): ?>
            <div class="friends-list-full">
                <?php foreach ($friends as $friend): 
                    $friend_user = is_array($friend) ? Simuze_User::get($friend['id']) : $friend;
                    // Get avatar URL using SIMEZU fetch method
                    $friend_avatar_url = '';
                    $friend_simezu_id = '';
                    if ($friend_user) {
                        $friend_simezu_id = $friend_user->simezu_id ?? '';
                        if (!empty($friend_simezu_id) && class_exists('Simuze_Avatar')) {
                            $avatar = Simuze_Avatar::get_instance();
                            $friend_avatar_url = $avatar->get_user_avatar_url($friend_simezu_id, 'thumb', array('cache_only' => false));
                            
                            // Fallback: if avatar_url is default/placeholder, use direct Simezu endpoint
                            if (empty($friend_avatar_url) || strpos($friend_avatar_url, 'default-avatar') !== false || strpos($friend_avatar_url, 'data:image') !== false) {
                                $api_url = rtrim(md_home_url('/api/auth'), '/');
                                $friend_avatar_url = $api_url . '/users/' . urlencode($friend_simezu_id) . '/avatar/image?size=60';
                            }
                        } else {
                            $friend_avatar_url = $friend_user->get_avatar_url(60, array('cache_only' => false));
                        }
                    }
                    if (empty($friend_avatar_url)) {
                        $friend_avatar_url = isset($friend['avatar_url']) ? $friend['avatar_url'] : theme_uri('/images/profile_default.jpg');
                    }
                    if (empty($friend_avatar_url)) {
                        $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                        $friend_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : theme_uri('/images/profile_default.jpg');
                    }
                ?>
                    <div class="friend-row-full" data-user-id="<?php echo esc_attr($friend['id']); ?>">
                        <a href="<?php echo esc_url(md_home_url('/user/' . $friend['username'] . '/')); ?>" class="friend-link-full">
                            <img src="<?php echo esc_url($friend_avatar_url); ?>" 
                                 alt="<?php echo esc_attr($friend['display_name']); ?>" 
                                 class="friend-avatar"
                                 <?php if (!empty($friend_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($friend_simezu_id); ?>"<?php endif; ?>>
                            <div class="friend-info-col">
                                <span class="friend-name"><?php echo esc_html($friend['display_name']); ?></span>
                                <span class="friend-username">@<?php echo esc_html($friend['username']); ?></span>
                            </div>
                        </a>
                        <?php if ($is_own_profile): ?>
                        <div class="friend-actions-col">
                            <a href="<?php echo esc_url(add_query_arg(array('tab' => 'messages', 'to' => $friend['id']), $user->get_url())); ?>" 
                               class="action-btn-list" title="Send message">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <button type="button" class="action-btn-list btn-unfriend" 
                                    data-user-id="<?php echo esc_attr($friend['id']); ?>" title="Unfriend">
                                <i class="fas fa-user-minus"></i>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state-full">
                <i class="fas fa-user-friends"></i>
                <h3><?php echo $search ? 'No friends found' : 'No friends yet'; ?></h3>
                <?php if ($is_own_profile && !$search): ?>
                    <p>Start connecting with other users!</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php elseif ($view === 'requests' && $is_own_profile): ?>
        <!-- Friend Requests -->
        <h4>Friend Requests</h4>
        
        <?php if (!empty($pending_requests)): ?>
            <div class="requests-list-full">
                <?php foreach ($pending_requests as $request): 
                    $requester = Simuze_User::get($request['user_id']);
                    if (!$requester) continue;
                ?>
                    <div class="request-row-full" data-request-id="<?php echo esc_attr($request['id']); ?>">
                        <a href="<?php echo esc_url($requester->get_url()); ?>" class="requester-link-full">
                            <?php
                            // Get avatar URL using SIMEZU fetch method
                            $requester_avatar_url = '';
                            $requester_simezu_id = $requester->simezu_id ?? '';
                            if (!empty($requester_simezu_id) && class_exists('Simuze_Avatar')) {
                                $avatar = Simuze_Avatar::get_instance();
                                $requester_avatar_url = $avatar->get_user_avatar_url($requester_simezu_id, 'thumb', array('cache_only' => false));
                                
                                // Fallback: if avatar_url is default/placeholder, use direct Simezu endpoint
                                if (empty($requester_avatar_url) || strpos($requester_avatar_url, 'default-avatar') !== false || strpos($requester_avatar_url, 'data:image') !== false) {
                                    $api_url = rtrim(md_home_url('/api/auth'), '/');
                                    $requester_avatar_url = $api_url . '/users/' . urlencode($requester_simezu_id) . '/avatar/image?size=60';
                                }
                            } else {
                                $requester_avatar_url = $requester->get_avatar_url(60, array('cache_only' => false));
                            }
                            if (empty($requester_avatar_url)) {
                                $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                                $requester_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : theme_uri('/images/profile_default.jpg');
                            }
                            ?>
                            <img src="<?php echo esc_url($requester_avatar_url); ?>" 
                                 alt="" 
                                 class="requester-avatar"
                                 <?php if (!empty($requester_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($requester_simezu_id); ?>"<?php endif; ?>>
                            <div class="requester-info-col">
                                <span class="requester-name"><?php echo esc_html($requester->display_name); ?></span>
                                <span class="requester-username">@<?php echo esc_html($requester->username); ?></span>
                                <span class="request-time"><?php echo human_time_diff(strtotime($request['created_at'])); ?> ago</span>
                            </div>
                        </a>
                        <div class="request-actions-col">
                            <button type="button" class="btn btn-primary btn-accept" data-user-id="<?php echo esc_attr($request['user_id']); ?>">
                                Accept
                            </button>
                            <button type="button" class="btn btn-secondary btn-decline" data-user-id="<?php echo esc_attr($request['user_id']); ?>">
                                Decline
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state-full">
                <i class="fas fa-inbox"></i>
                <h3>No pending requests</h3>
            </div>
        <?php endif; ?>
        
        <?php elseif ($view === 'sent' && $is_own_profile): ?>
        <!-- Sent Requests -->
        <h4>Sent Requests</h4>
        
        <?php if (!empty($sent_requests)): ?>
            <div class="requests-list-full">
                <?php foreach ($sent_requests as $request): 
                    $recipient = Simuze_User::get($request['friend_id']);
                    if (!$recipient) continue;
                ?>
                    <div class="request-row-full sent" data-request-id="<?php echo esc_attr($request['id']); ?>">
                        <a href="<?php echo esc_url($recipient->get_url()); ?>" class="requester-link-full">
                            <?php
                            // Get avatar URL using SIMEZU fetch method
                            $recipient_avatar_url = '';
                            $recipient_simezu_id = $recipient->simezu_id ?? '';
                            if (!empty($recipient_simezu_id) && class_exists('Simuze_Avatar')) {
                                $avatar = Simuze_Avatar::get_instance();
                                $recipient_avatar_url = $avatar->get_user_avatar_url($recipient_simezu_id, 'thumb', array('cache_only' => false));
                                
                                // Fallback: if avatar_url is default/placeholder, use direct Simezu endpoint
                                if (empty($recipient_avatar_url) || strpos($recipient_avatar_url, 'default-avatar') !== false || strpos($recipient_avatar_url, 'data:image') !== false) {
                                    $api_url = rtrim(md_home_url('/api/auth'), '/');
                                    $recipient_avatar_url = $api_url . '/users/' . urlencode($recipient_simezu_id) . '/avatar/image?size=60';
                                }
                            } else {
                                $recipient_avatar_url = $recipient->get_avatar_url(60, array('cache_only' => false));
                            }
                            if (empty($recipient_avatar_url)) {
                                $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                                $recipient_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : theme_uri('/images/profile_default.jpg');
                            }
                            ?>
                            <img src="<?php echo esc_url($recipient_avatar_url); ?>" 
                                 alt="" 
                                 class="requester-avatar"
                                 <?php if (!empty($recipient_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($recipient_simezu_id); ?>"<?php endif; ?>>
                            <div class="requester-info-col">
                                <span class="requester-name"><?php echo esc_html($recipient->display_name); ?></span>
                                <span class="requester-username">@<?php echo esc_html($recipient->username); ?></span>
                                <span class="request-time">Sent <?php echo human_time_diff(strtotime($request['created_at'])); ?> ago</span>
                            </div>
                        </a>
                        <div class="request-actions-col">
                            <span class="status-pending">Pending</span>
                            <button type="button" class="btn btn-secondary btn-cancel" data-user-id="<?php echo esc_attr($request['friend_id']); ?>">
                                Cancel
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state-full">
                <i class="fas fa-paper-plane"></i>
                <h3>No sent requests</h3>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Accept friend request
    $(document).on('click', '.btn-accept', function() {
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const $row = $btn.closest('.request-row-full');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'friends/accept',
            type: 'POST',
            data: JSON.stringify({ user_id: userId }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function() {
                $row.fadeOut(function() { $(this).remove(); });
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to accept request';
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError(errorMsg);
                } else {
                    alert(errorMsg);
                }
                $btn.prop('disabled', false).html('Accept');
            }
        });
    });
    
    // Decline friend request
    $(document).on('click', '.btn-decline', function() {
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const $row = $btn.closest('.request-row-full');
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'friends/reject',
            type: 'POST',
            data: JSON.stringify({ user_id: userId }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function() {
                $row.fadeOut(function() { $(this).remove(); });
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to decline request';
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError(errorMsg);
                } else {
                    alert(errorMsg);
                }
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Cancel sent request
    $(document).on('click', '.btn-cancel', function() {
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const $row = $btn.closest('.request-row-full');
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'friends/cancel',
            type: 'POST',
            data: JSON.stringify({ user_id: userId }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function() {
                $row.fadeOut(function() { $(this).remove(); });
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to cancel request';
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError(errorMsg);
                } else {
                    alert(errorMsg);
                }
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Unfriend
    $(document).on('click', '.btn-unfriend', function() {
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const $row = $btn.closest('.friend-row-full');
        
        const unfriendUser = function() {
            $.ajax({
                url: simuzeCommunity.apiUrl + 'friends/unfriend',
                type: 'POST',
                data: JSON.stringify({ user_id: userId }),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                },
                success: function() {
                    $row.fadeOut(function() { $(this).remove(); });
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to remove friend';
                    if (typeof simuzeAlertError === 'function') {
                        simuzeAlertError(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                }
            });
        };
        
        if (typeof simuzeConfirm === 'function') {
            simuzeConfirm('Are you sure you want to remove this friend?', 'Remove Friend', unfriendUser);
        } else {
            if (confirm('Are you sure you want to remove this friend?')) {
                unfriendUser();
            }
        }
    });
});
</script>
