<?php
/**
 * Group Invites Template
 * For admins to manage group invitations
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get pending invitations
$pending_invitations = $group->get_invitations('pending');
$sent_invitations = $group->get_invitations('sent');
?>

<div class="group-invites">
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
                                    <span class="user-email"><?php echo esc_html($invited_user->email); ?></span>
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

<style>
.group-invites {
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
    padding-left: 20px;
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
    margin: 0;
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

.search-result-item.no-results:hover {
    background: transparent;
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

.invitation-user .user-email {
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
                                    <img src="${user.avatar_url || '<?php echo esc_url(theme_uri('/images/profile_default.jpg')); ?>'}"
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
        }, 50); // Very short delay for instant feel
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
                showSuccessModal('Invitations sent successfully!', function() {
                    window.location.reload();
                });
            })
            .catch(function() {
                showErrorModal('Some invitations failed to send.');
                $btn.prop('disabled', false).text('Send Invitations');
            });
    });
    
    // Cancel invitation
    $('.cancel-invitation').on('click', function() {
        const invitationId = $(this).data('id');
        const $item = $(this).closest('.invitation-item');
        
        // Use modal confirmation instead of native confirm
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
                        // Update pending count - count remaining items
                        const pendingCount = Math.max(0, $('.invitation-item').length);
                        const $pendingSection = $('.invite-section').has('h3:contains("Pending Invitations")');
                        const $h3 = $pendingSection.find('h3');
                        const $list = $pendingSection.find('.invitations-list');
                        const $noInvitations = $pendingSection.find('.no-invitations');
                        
                        // Update the count in the heading
                        $h3.text('Pending Invitations (' + pendingCount + ')');
                        
                        // Show/hide the "no invitations" message
                        if (pendingCount === 0) {
                            if ($noInvitations.length === 0) {
                                $list.after('<p class="no-invitations">No pending invitations.</p>');
                            }
                        } else {
                            $noInvitations.remove();
                        }
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to cancel invitation.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showErrorModal(errorMessage);
                }
            });
        };
        
        if (typeof simuzeConfirm === 'function') {
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
                showErrorModal('Failed to resend invitation.');
                $btn.prop('disabled', false).text('Resend');
            }
        });
    });
    
    // Modal functions for success/error messages
    function showSuccessModal(message, onClose) {
        const modalHtml = `
            <div id="invite-success-modal" class="simuze-modal-overlay" style="display: flex !important;">
                <div class="simuze-modal">
                    <div class="simuze-modal-header">
                        <h3 class="simuze-modal-title">
                            <i class="fas fa-check-circle" style="color: #51ac32; margin-right: 8px;"></i>
                            Success
                        </h3>
                        <button type="button" class="simuze-modal-close" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="simuze-modal-content">
                        <p class="simuze-modal-message">${message}</p>
                    </div>
                    <div class="simuze-modal-actions">
                        <button type="button" class="simuze-modal-btn button button-primary" id="invite-modal-ok">
                            OK
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Remove any existing modal
        $('#invite-success-modal').remove();
        
        // Add modal to body
        $('body').append(modalHtml);
        $('body').addClass('modal-open');
        
        // Close handlers
        $('#invite-modal-ok, #invite-success-modal .simuze-modal-close').on('click', function() {
            $('#invite-success-modal').fadeOut(200, function() {
                $(this).remove();
                $('body').removeClass('modal-open');
                if (onClose) onClose();
            });
        });
        
        // Close on overlay click
        $('#invite-success-modal').on('click', function(e) {
            if ($(e.target).hasClass('simuze-modal-overlay')) {
                $(this).fadeOut(200, function() {
                    $(this).remove();
                    $('body').removeClass('modal-open');
                    if (onClose) onClose();
                });
            }
        });
        
        // Close on Escape key
        $(document).on('keydown.inviteModal', function(e) {
            if (e.key === 'Escape' && $('#invite-success-modal').length) {
                $('#invite-success-modal').fadeOut(200, function() {
                    $(this).remove();
                    $('body').removeClass('modal-open');
                    $(document).off('keydown.inviteModal');
                    if (onClose) onClose();
                });
            }
        });
    }
    
    function showErrorModal(message) {
        const modalHtml = `
            <div id="invite-error-modal" class="simuze-modal-overlay" style="display: flex !important;">
                <div class="simuze-modal">
                    <div class="simuze-modal-header">
                        <h3 class="simuze-modal-title">
                            <i class="fas fa-exclamation-circle" style="color: #dc3545; margin-right: 8px;"></i>
                            Error
                        </h3>
                        <button type="button" class="simuze-modal-close" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="simuze-modal-content">
                        <p class="simuze-modal-message">${message}</p>
                    </div>
                    <div class="simuze-modal-actions">
                        <button type="button" class="simuze-modal-btn button button-primary" id="invite-error-modal-ok">
                            OK
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Remove any existing modal
        $('#invite-error-modal').remove();
        
        // Add modal to body
        $('body').append(modalHtml);
        $('body').addClass('modal-open');
        
        // Close handlers
        $('#invite-error-modal-ok, #invite-error-modal .simuze-modal-close').on('click', function() {
            $('#invite-error-modal').fadeOut(200, function() {
                $(this).remove();
                $('body').removeClass('modal-open');
            });
        });
        
        // Close on overlay click
        $('#invite-error-modal').on('click', function(e) {
            if ($(e.target).hasClass('simuze-modal-overlay')) {
                $(this).fadeOut(200, function() {
                    $(this).remove();
                    $('body').removeClass('modal-open');
                });
            }
        });
        
        // Close on Escape key
        $(document).on('keydown.inviteErrorModal', function(e) {
            if (e.key === 'Escape' && $('#invite-error-modal').length) {
                $('#invite-error-modal').fadeOut(200, function() {
                    $(this).remove();
                    $('body').removeClass('modal-open');
                    $(document).off('keydown.inviteErrorModal');
                });
            }
        });
    }
});
</script>








