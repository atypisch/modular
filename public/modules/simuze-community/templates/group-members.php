<?php
/**
 * Group Members Template
 * Includes Members list and Invites management (for admins)
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_admin = $current_user && $group->can_manage($current_user->id);

$members_result = $group->get_members(array('per_page' => 50));
$members = $members_result['members'];

// Get pending invitations (for admin section)
$pending_invitations = $is_admin ? $group->get_invitations('pending') : array();
?>

<div class="group-members-page">
    <!-- Members Section -->
<div class="group-members">
    <h2>Members (<?php echo esc_html($members_result['count']); ?>)</h2>
    
    <?php if (!empty($members)): ?>
        <ul class="members-list">
            <?php foreach ($members as $member): 
                $member_user = Simuze_User::get($member['id']);
                // Get avatar URL using SIMEZU fetch method
                $member_avatar_url = '';
                $member_simezu_id = '';
                if ($member_user) {
                    $member_simezu_id = $member_user->simezu_id ?? '';
                    if (!empty($member_simezu_id) && class_exists('Simuze_Avatar')) {
                        $avatar = Simuze_Avatar::get_instance();
                        $member_avatar_url = $avatar->get_user_avatar_url($member_simezu_id, 'thumb', array('cache_only' => false));
                        
                        // Fallback: if avatar_url is default/placeholder, use direct Simezu endpoint
                        if (empty($member_avatar_url) || strpos($member_avatar_url, 'default-avatar') !== false || strpos($member_avatar_url, 'data:image') !== false) {
                            $api_url = rtrim(md_home_url('/api/auth'), '/');
                            $member_avatar_url = $api_url . '/users/' . urlencode($member_simezu_id) . '/avatar/image?size=64';
                        }
                    } else {
                        $member_avatar_url = $member_user->get_avatar_url('thumb', array('cache_only' => false));
                    }
                }
                if (empty($member_avatar_url)) {
                    $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                    $member_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : theme_uri('') . '/images/profile_default.jpg';
                }
            ?>
                <li class="member-item">
                    <img src="<?php echo esc_url($member_avatar_url); ?>" 
                         alt="<?php echo esc_attr($member['display_name']); ?>" 
                         class="member-avatar user-avatar"
                         <?php if (!empty($member_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($member_simezu_id); ?>"<?php endif; ?>
                         onerror="this.src='<?php echo esc_url(theme_uri('') . '/images/profile_default.jpg'); ?>';">
                    <div class="member-info">
                        <a href="<?php echo esc_url(md_home_url('/user/' . $member['username'] . '/')); ?>">
                            <?php echo esc_html($member['display_name']); ?>
                        </a>
                        <span class="member-role"><?php echo esc_html($member['role']); ?></span>
                    </div>
                    <?php if ($is_admin && $member['user_id'] != $group->creator_id): ?>
                        <div class="member-actions">
                            <select class="member-role-select" data-user-id="<?php echo esc_attr($member['user_id']); ?>" data-group-id="<?php echo esc_attr($group->id); ?>">
                                <option value="member" <?php selected($member['role'], 'member'); ?>>Member</option>
                                <option value="mod" <?php selected($member['role'], 'mod'); ?>>Moderator</option>
                                <option value="admin" <?php selected($member['role'], 'admin'); ?>>Admin</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No members yet.</p>
    <?php endif; ?>
</div>

    <!-- Invites Section (Admin Only) -->
    <?php if ($is_admin): ?>
        <div class="group-invites-section" style="margin-top: 40px; padding-top: 40px; border-top: 1px solid rgba(255,255,255,0.1);">
            <h2>Manage Invitations</h2>
            
            <div class="invites-sections">
                <!-- Invite New Users -->
                <div class="invite-section">
                    <h3>Invite Users</h3>
                    <div class="invite-form">
                        <div class="search-input-wrapper">
                            <input type="text" id="invite-search" placeholder="Type @username to search users..." class="search-input">
                            <div id="invite-search-results" class="search-results"></div>
                        </div>
                        
                        <div id="selected-users" class="selected-users"></div>
                        
                        <div class="invite-message">
                            <label for="invite-message">Personal message (optional)</label>
                            <textarea id="invite-message" rows="3" placeholder="Add a personal message to your invitation..."></textarea>
                        </div>
                        
                        <button type="button" id="send-invites" class="button button-primary" disabled>Send Invitations</button>
                    </div>
                </div>
                
                <!-- Pending Invitations -->
                <div class="invite-section">
                    <h3>Pending Invitations (<?php echo count($pending_invitations); ?>)</h3>
                    <?php if (!empty($pending_invitations)): ?>
                        <div class="invitations-list">
                            <?php foreach ($pending_invitations as $invitation): 
                                $invited_user = Simuze_User::get($invitation['user_id']);
                                if (!$invited_user) continue;
                            ?>
                                <div class="invitation-item" data-invitation-id="<?php echo esc_attr($invitation['id']); ?>">
                                    <div class="invitation-user">
                                        <img src="<?php echo esc_url($invited_user->get_avatar_url(40, true)); ?>" alt="" class="user-avatar">
                                        <div class="user-info">
                                            <span class="user-name"><?php echo esc_html($invited_user->display_name); ?></span>
                                            <span class="user-username">@<?php echo esc_html($invited_user->username); ?></span>
                                        </div>
                                    </div>
                                    <div class="invitation-meta">
                                        <span class="invited-date">Invited <?php echo esc_html(human_time_diff(strtotime($invitation['created_at']))); ?> ago</span>
                                    </div>
                                    <div class="invitation-actions">
                                        <button class="button cancel-invitation" data-id="<?php echo esc_attr($invitation['id']); ?>">Cancel</button>
                                        <button class="button resend-invitation" data-id="<?php echo esc_attr($invitation['id']); ?>">Resend</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-invitations">No pending invitations.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Include invites JavaScript and CSS -->
        <style>
        .group-invites-section {
            padding: 20px 0;
        }

        .invites-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .invite-section {
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            padding: 25px;
        }

        .invite-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .invite-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .search-input-wrapper {
            position: relative;
            width: 100%;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .search-input:focus {
            outline: none;
            border-color: rgba(255,255,255,0.3);
        }

        .search-results {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            right: 0;
            max-height: 300px;
            overflow-y: auto;
            background: rgba(0,0,0,0.95);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .search-results.active {
            display: block;
        }

        .search-result-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .search-result-item:hover {
            background: rgba(255,255,255,0.1);
        }

        .search-result-item img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .search-result-item .user-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .search-result-item .user-name {
            font-weight: 500;
            color: #fff;
        }

        .search-result-item .user-username {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .search-result-item.no-results {
            cursor: default;
            color: rgba(255, 255, 255, 0.5);
            font-style: italic;
        }

        .selected-users {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .selected-user {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .selected-user .remove {
            cursor: pointer;
            opacity: 0.7;
        }

        .selected-user .remove:hover {
            opacity: 1;
        }

        .invite-message label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.7);
        }

        .invite-message textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            resize: vertical;
        }

        .invitations-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .invitation-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: rgba(255,255,255,0.03);
            border-radius: 8px;
        }

        .invitation-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .invitation-user .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .invitation-user .user-name {
            display: block;
            font-weight: 500;
        }

        .invitation-user .user-username {
            display: block;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
        }

        .invitation-meta {
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
        }

        .invitation-actions {
            display: flex;
            gap: 8px;
        }

        .no-invitations {
            text-align: center;
            padding: 30px;
            color: rgba(255,255,255,0.5);
        }

        @media (max-width: 768px) {
            .invites-sections {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let selectedUsers = [];
            let searchTimeout;
            
            // @mention-style search users
            $('#invite-search').on('input', function() {
                const input = $(this);
                const value = input.val();
                const cursorPos = input[0].selectionStart || value.length;
                
                // Find @ symbol and text after it
                const textBeforeCursor = value.substring(0, cursorPos);
                const lastAtIndex = textBeforeCursor.lastIndexOf('@');
                
                // Only search if @ is present and there's text after it
                if (lastAtIndex === -1) {
                    $('#invite-search-results').removeClass('active');
                    return;
                }
                
                const query = textBeforeCursor.substring(lastAtIndex + 1).trim();
                
                clearTimeout(searchTimeout);
                
                // Search immediately if query is at least 1 character after @
                if (query.length < 1) {
                    $('#invite-search-results').removeClass('active');
                    return;
                }
                
                // Instant search with minimal delay (50ms for debouncing)
                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: simuzeCommunity.apiUrl + 'users/search',
                        data: { query: query, limit: 10 },
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                        },
                        success: function(response) {
                            const results = $('#invite-search-results');
                            results.empty();
                            
                            if (response && response.length > 0) {
                                response.forEach(function(user) {
                                    // Skip already selected users
                                    if (selectedUsers.find(u => u.id === user.id)) return;
                                    
                                    results.append(`
                                        <div class="search-result-item" data-user-id="${user.id}" data-user-name="${user.display_name}" data-username="${user.username || ''}" ${user.simezu_id ? 'data-simezu-user-id="' + user.simezu_id + '"' : ''}>
                                            <img src="${user.avatar_url || '<?php echo esc_url(theme_uri('') . '/images/profile_default.jpg'); ?>'}" 
                                                 alt=""
                                                 ${user.simezu_id ? 'data-simezu-user-id="' + user.simezu_id + '"' : ''}>
                                            <div class="user-info">
                                                <span class="user-name">${user.display_name || user.username}</span>
                                                <span class="user-username">@${user.username || ''}</span>
                                            </div>
                                        </div>
                                    `);
                                });
                                results.addClass('active');
                            } else {
                                results.html('<div class="search-result-item no-results">No users found</div>');
                                results.addClass('active');
                            }
                        },
                        error: function() {
                            $('#invite-search-results').removeClass('active');
                        }
                    });
                }, 50);
            });
            
            // Select user from search results
            $(document).on('click', '.search-result-item:not(.no-results)', function() {
                const userId = $(this).data('user-id');
                const userName = $(this).data('user-name');
                const username = $(this).data('username');
                
                if (!userId) return;
                
                selectedUsers.push({ id: userId, name: userName, username: username });
                updateSelectedUsers();
                
                // Clear the search input
                $('#invite-search').val('');
                $('#invite-search-results').removeClass('active');
            });
            
            // Remove selected user
            $(document).on('click', '.selected-user .remove', function() {
                const userId = $(this).closest('.selected-user').data('user-id');
                selectedUsers = selectedUsers.filter(u => u.id !== userId);
                updateSelectedUsers();
            });
            
            function updateSelectedUsers() {
                const container = $('#selected-users');
                container.empty();
                
                selectedUsers.forEach(function(user) {
                    container.append(`
                        <div class="selected-user" data-user-id="${user.id}">
                            <span>${user.name}${user.username ? ' (@' + user.username + ')' : ''}</span>
                            <span class="remove">&times;</span>
                        </div>
                    `);
                });
                
                $('#send-invites').prop('disabled', selectedUsers.length === 0);
            }
            
            // Send invitations
            $('#send-invites').on('click', function() {
                if (selectedUsers.length === 0) return;
                
                const message = $('#invite-message').val();
                const $btn = $(this);
                
                $btn.prop('disabled', true).text('Sending...');
                
                const promises = selectedUsers.map(function(user) {
                    return $.ajax({
                        url: simuzeCommunity.apiUrl + 'groups/<?php echo $group->id; ?>/invite',
                        method: 'POST',
                        data: JSON.stringify({ user_id: user.id, message: message }),
                        contentType: 'application/json',
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                        }
                    });
                });
                
                Promise.all(promises)
                    .then(function() {
                        if (typeof simuzeShowModal === 'function') {
                            simuzeShowModal('Invitations sent successfully!', 'success', function() {
                                window.location.reload();
                            });
                        } else {
                            alert('Invitations sent successfully!');
                            window.location.reload();
                        }
                    })
                    .catch(function() {
                        if (typeof simuzeShowModal === 'function') {
                            simuzeShowModal('Some invitations failed to send.', 'error');
                        } else {
                            alert('Some invitations failed to send.');
                        }
                        $btn.prop('disabled', false).text('Send Invitations');
                    });
            });
            
            // Cancel invitation
            $('.cancel-invitation').on('click', function() {
                const invitationId = $(this).data('id');
                const $item = $(this).closest('.invitation-item');
                
                const proceedWithCancel = function() {
                    $.ajax({
                        url: simuzeCommunity.apiUrl + 'groups/<?php echo $group->id; ?>/invitations/' + invitationId,
                        method: 'DELETE',
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                        },
                        success: function() {
                            $item.fadeOut(function() { 
                                $(this).remove();
                                const pendingCount = Math.max(0, $('.invitation-item').length);
                                $('.invite-section h3').first().next().text('Pending Invitations (' + pendingCount + ')');
                                if (pendingCount === 0) {
                                    $('.invitations-list').after('<p class="no-invitations">No pending invitations.</p>');
                                }
                            });
                        },
                        error: function(xhr) {
                            let errorMessage = 'Failed to cancel invitation.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            if (typeof simuzeShowModal === 'function') {
                                simuzeShowModal(errorMessage, 'error');
                            } else {
                                alert(errorMessage);
                            }
                        }
                    });
                };
                
                if (typeof simuzeShowDeleteModal === 'function') {
                    simuzeShowDeleteModal('Are you sure you want to cancel this invitation?', proceedWithCancel);
                } else if (typeof simuzeConfirm === 'function') {
                    simuzeConfirm('Are you sure you want to cancel this invitation?', 'Cancel Invitation', proceedWithCancel);
                } else {
                    if (confirm('Are you sure you want to cancel this invitation?')) {
                        proceedWithCancel();
                    }
                }
            });
            
            // Resend invitation
            $('.resend-invitation').on('click', function() {
                const invitationId = $(this).data('id');
                const $btn = $(this);
                
                $btn.prop('disabled', true).text('Sending...');
                
                $.ajax({
                    url: simuzeCommunity.apiUrl + 'groups/<?php echo $group->id; ?>/invitations/' + invitationId + '/resend',
                    method: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                    },
                    success: function() {
                        $btn.text('Sent!');
                        setTimeout(function() {
                            $btn.prop('disabled', false).text('Resend');
                        }, 2000);
                    },
                    error: function() {
                        if (typeof simuzeShowModal === 'function') {
                            simuzeShowModal('Failed to resend invitation.', 'error');
                        } else {
                            alert('Failed to resend invitation.');
                        }
                        $btn.prop('disabled', false).text('Resend');
                    }
                });
            });
        });
        </script>
    <?php endif; ?>
</div>




