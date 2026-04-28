<?php
/**
 * User Profile Messages Template
 * Full width layout matching Playlists style
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_own_profile = $current_user && $current_user->id === $user->id;

if (!$is_own_profile) {
    echo '<div class="access-denied"><p>You can only view your own messages.</p></div>';
    return;
}

// Get threads
$threads = Simuze_Message::get_threads($user->id);

// Check if viewing a specific thread or composing new message
$active_thread = isset($_GET['thread']) ? sanitize_text_field($_GET['thread']) : null;
$compose_to = isset($_GET['to']) ? intval($_GET['to']) : null;

// Get messages for active thread
$messages = array();
$thread_participant = null;
if ($active_thread) {
    $messages = Simuze_Message::get_messages($user->id, array('thread_id' => $active_thread, 'per_page' => 50));
    // Mark as read
    foreach ($messages as $msg) {
        if ($msg['recipient_id'] == $user->id && !$msg['is_read']) {
            Simuze_Message::mark_as_read($msg['id'], $user->id);
        }
    }
    // Get the other participant
    if (!empty($messages)) {
        $other_id = $messages[0]['sender_id'] == $user->id ? $messages[0]['recipient_id'] : $messages[0]['sender_id'];
        $thread_participant = Simuze_User::get($other_id);
    }
}

// Get recipient for new message
$compose_recipient = null;
if ($compose_to) {
    $compose_recipient = Simuze_User::get($compose_to);
}
?>

<div class="messages-page-full">
    <div class="messages-layout-full">
        <!-- Threads Sidebar -->
        <div class="threads-sidebar-full">
            <div class="threads-header-full">
                <h3>Messages</h3>
                <button type="button" class="btn-compose" id="new-message-btn">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
            
            <div class="threads-list-full">
                <?php if (empty($threads)): ?>
                    <div class="no-threads">
                        <p>No conversations yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($threads as $thread): 
                        // Get the other participant
                        $last_msg = Simuze_Message::get_messages($user->id, array('thread_id' => $thread['thread_id'], 'per_page' => 1));
                        $other_id = !empty($last_msg) ? ($last_msg[0]['sender_id'] == $user->id ? $last_msg[0]['recipient_id'] : $last_msg[0]['sender_id']) : null;
                        $participant = $other_id ? Simuze_User::get($other_id) : null;
                        $is_active = $active_thread === $thread['thread_id'];
                    ?>
                        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'messages', 'thread' => $thread['thread_id']), $user->get_url())); ?>" 
                           class="thread-item-full <?php echo $is_active ? 'active' : ''; ?> <?php echo $thread['unread_count'] > 0 ? 'unread' : ''; ?>">
                            <?php if ($participant): 
                                // Get avatar URL using SIMEZU fetch method
                                $participant_avatar_url = '';
                                $participant_simezu_id = $participant->simezu_id ?? '';
                                if (!empty($participant_simezu_id) && class_exists('Simuze_Avatar')) {
                                    $avatar = Simuze_Avatar::get_instance();
                                    $participant_avatar_url = $avatar->get_user_avatar_url($participant_simezu_id, 'thumb', array('cache_only' => false));
                                    
                                    // Fallback: if avatar_url is default/placeholder, use direct Simezu endpoint
                                    if (empty($participant_avatar_url) || strpos($participant_avatar_url, 'default-avatar') !== false || strpos($participant_avatar_url, 'data:image') !== false) {
                                        $api_url = get_option('simuze_auth_api_url', 'http://localhost/simezu/api.php/auth');
                                        $api_url = str_replace('/api.php/auth', '', $api_url);
                                        $participant_avatar_url = $api_url . '/api/auth/users/' . urlencode($participant_simezu_id) . '/avatar/image?size=40';
                                    }
                                } else {
                                    $participant_avatar_url = $participant->get_avatar_url(40, array('cache_only' => false));
                                }
                                if (empty($participant_avatar_url)) {
                                    $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                                    $participant_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : get_template_directory_uri() . '/images/profile_default.jpg';
                                }
                            ?>
                                <img src="<?php echo esc_url($participant_avatar_url); ?>" 
                                     alt="" 
                                     class="thread-avatar user-avatar"
                                     <?php if (!empty($participant_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($participant_simezu_id); ?>"<?php endif; ?>>
                                <div class="thread-info">
                                    <span class="thread-name"><?php echo esc_html($participant->display_name); ?></span>
                                    <span class="thread-preview">
                                        <?php 
                                        if (!empty($last_msg)) {
                                            echo esc_html(wp_trim_words(strip_tags($last_msg[0]['message']), 8, '...'));
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="thread-meta">
                                    <span class="thread-time"><?php echo esc_html(human_time_diff(strtotime($thread['last_message_at']))); ?></span>
                                    <?php if ($thread['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $thread['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Message Area -->
        <div class="message-area-full">
            <?php if ($active_thread && $thread_participant): ?>
                <!-- Conversation View -->
                <div class="conversation-header-full">
                    <a href="<?php echo esc_url($thread_participant->get_url()); ?>" class="participant-info">
                        <?php
                        // Get avatar URL using SIMEZU fetch method
                        $thread_participant_avatar_url = '';
                        $thread_participant_simezu_id = $thread_participant->simezu_id ?? '';
                        if (!empty($thread_participant_simezu_id) && class_exists('Simuze_Avatar')) {
                            $avatar = Simuze_Avatar::get_instance();
                            $thread_participant_avatar_url = $avatar->get_user_avatar_url($thread_participant_simezu_id, 'thumb', array('cache_only' => false));
                            
                            // Fallback: if avatar_url is default/placeholder, use direct Simezu endpoint
                            if (empty($thread_participant_avatar_url) || strpos($thread_participant_avatar_url, 'default-avatar') !== false || strpos($thread_participant_avatar_url, 'data:image') !== false) {
                                $api_url = get_option('simuze_auth_api_url', 'http://localhost/simezu/api.php/auth');
                                $api_url = str_replace('/api.php/auth', '', $api_url);
                                $thread_participant_avatar_url = $api_url . '/api/auth/users/' . urlencode($thread_participant_simezu_id) . '/avatar/image?size=40';
                            }
                        } else {
                            $thread_participant_avatar_url = $thread_participant->get_avatar_url(40, array('cache_only' => false));
                        }
                        if (empty($thread_participant_avatar_url)) {
                            $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                            $thread_participant_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : get_template_directory_uri() . '/images/profile_default.jpg';
                        }
                        ?>
                        <img src="<?php echo esc_url($thread_participant_avatar_url); ?>" 
                             alt="" 
                             class="participant-avatar user-avatar"
                             <?php if (!empty($thread_participant_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($thread_participant_simezu_id); ?>"<?php endif; ?>>
                        <div>
                            <span class="participant-name"><?php echo esc_html($thread_participant->display_name); ?></span>
                            <span class="participant-handle">@<?php echo esc_html($thread_participant->username); ?></span>
                        </div>
                    </a>
                </div>
                
                <div class="messages-container-full" id="messages-container">
                    <?php 
                    $messages = array_reverse($messages); // Show oldest first
                    foreach ($messages as $msg): 
                        $is_own = $msg['sender_id'] == $user->id;
                    ?>
                        <div class="message <?php echo $is_own ? 'own' : 'other'; ?>">
                            <div class="message-content">
                                <?php echo wp_kses_post($msg['message']); ?>
                            </div>
                            <span class="message-time"><?php echo esc_html(date('H:i', strtotime($msg['created_at']))); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="message-composer-full">
                    <form id="send-message-form" data-thread="<?php echo esc_attr($active_thread); ?>" data-recipient="<?php echo esc_attr($thread_participant->id); ?>">
                        <div class="composer-input-full">
                            <div id="message-input" class="message-input-full" contenteditable="true" placeholder="Type a message..." data-mention-enabled="true"></div>
                        </div>
                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
                
            <?php elseif ($compose_to && $compose_recipient): ?>
                <!-- New Message View -->
                <div class="conversation-header-full">
                    <a href="<?php echo esc_url($compose_recipient->get_url()); ?>" class="participant-info">
                        <?php
                        // Get avatar URL using SIMEZU fetch method
                        $compose_recipient_avatar_url = '';
                        $compose_recipient_simezu_id = $compose_recipient->simezu_id ?? '';
                        if (!empty($compose_recipient_simezu_id) && class_exists('Simuze_Avatar')) {
                            $avatar = Simuze_Avatar::get_instance();
                            $compose_recipient_avatar_url = $avatar->get_user_avatar_url($compose_recipient_simezu_id, 'thumb', array('cache_only' => false));
                            
                            // Fallback: if avatar_url is default/placeholder, use direct Simezu endpoint
                            if (empty($compose_recipient_avatar_url) || strpos($compose_recipient_avatar_url, 'default-avatar') !== false || strpos($compose_recipient_avatar_url, 'data:image') !== false) {
                                $api_url = get_option('simuze_auth_api_url', 'http://localhost/simezu/api.php/auth');
                                $api_url = str_replace('/api.php/auth', '', $api_url);
                                $compose_recipient_avatar_url = $api_url . '/api/auth/users/' . urlencode($compose_recipient_simezu_id) . '/avatar/image?size=40';
                            }
                        } else {
                            $compose_recipient_avatar_url = $compose_recipient->get_avatar_url(40, array('cache_only' => false));
                        }
                        if (empty($compose_recipient_avatar_url)) {
                            $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                            $compose_recipient_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : get_template_directory_uri() . '/images/profile_default.jpg';
                        }
                        ?>
                        <img src="<?php echo esc_url($compose_recipient_avatar_url); ?>" 
                             alt="" 
                             class="participant-avatar user-avatar"
                             <?php if (!empty($compose_recipient_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($compose_recipient_simezu_id); ?>"<?php endif; ?>>
                        <div>
                            <span class="participant-name"><?php echo esc_html($compose_recipient->display_name); ?></span>
                            <span class="participant-handle">@<?php echo esc_html($compose_recipient->username); ?></span>
                        </div>
                    </a>
                </div>
                
                <div class="messages-container-full" id="messages-container">
                    <div class="start-conversation">
                        <p>Start a new conversation with <?php echo esc_html($compose_recipient->display_name); ?></p>
                    </div>
                </div>
                
                <div class="message-composer-full">
                    <form id="send-message-form" data-recipient="<?php echo esc_attr($compose_recipient->id); ?>">
                        <div class="composer-input-full">
                            <div id="message-input" class="message-input-full" contenteditable="true" placeholder="Type a message..." data-mention-enabled="true"></div>
                        </div>
                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
                
            <?php else: ?>
                <!-- Empty State -->
                <div class="no-conversation">
                    <i class="fas fa-comments"></i>
                    <h3>Select a conversation</h3>
                    <p>Choose a conversation from the list or start a new one.</p>
                    <button type="button" class="btn btn-primary" id="start-new-btn">
                        <i class="fas fa-edit"></i> New Message
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div id="new-message-modal" class="simuze-modal" style="display: none;">
    <div class="simuze-modal-overlay"></div>
    <div class="simuze-modal-content">
        <button type="button" class="simuze-modal-close">&times;</button>
        <h2>New Message</h2>
        
        <div class="recipient-search">
            <label>To:</label>
            <div class="search-container">
                <input type="text" id="recipient-search" placeholder="Search users..." autocomplete="off">
                <div id="recipient-dropdown" class="search-dropdown"></div>
            </div>
            <div id="selected-recipient" class="selected-recipient" style="display: none;"></div>
        </div>
        
        <div class="message-compose-area">
            <div id="new-message-input" class="message-input-full" contenteditable="true" placeholder="Type your message..." data-mention-enabled="true"></div>
        </div>
        
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" id="cancel-new-message">Cancel</button>
            <button type="button" class="btn btn-primary" id="send-new-message">
                <i class="fas fa-paper-plane"></i> Send
            </button>
        </div>
    </div>
</div>

<!-- Mentions Dropdown -->
<div id="mentions-dropdown" class="mentions-dropdown" style="display: none;"></div>

<script>
jQuery(document).ready(function($) {
    // Scroll to bottom of messages
    function scrollToBottom() {
        const container = document.getElementById('messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
    scrollToBottom();
    
    // Send message
    $('#send-message-form').on('submit', function(e) {
        e.preventDefault();
        
        const $input = $('#message-input');
        const message = $input.html().trim();
        const recipientId = $(this).data('recipient');
        
        if (!message || message === '<br>') return;
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'messages',
            type: 'POST',
            data: JSON.stringify({
                recipient_id: recipientId,
                message: message
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function(response) {
                // Add message to UI
                const msgHtml = `
                    <div class="message own">
                        <div class="message-content">${message}</div>
                        <span class="message-time">Just now</span>
                    </div>
                `;
                $('#messages-container').append(msgHtml);
                $input.html('');
                scrollToBottom();
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to send message';
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError(errorMsg);
                } else {
                    alert(errorMsg);
                }
            }
        });
    });
    
    // New message modal
    $('#new-message-btn, #start-new-btn').on('click', function() {
        $('#new-message-modal').fadeIn(200);
    });
    
    $('#cancel-new-message, #new-message-modal .simuze-modal-close, #new-message-modal .simuze-modal-overlay').on('click', function() {
        $('#new-message-modal').fadeOut(200);
    });
    
    // Recipient search
    let searchTimeout;
    let selectedRecipient = null;
    
    $('#recipient-search').on('input', function() {
        const query = $(this).val().trim();
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            $('#recipient-dropdown').removeClass('show');
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: simuzeCommunity.apiUrl + 'search/users',
                type: 'GET',
                data: { query: query },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                },
                success: function(users) {
                    let html = '';
                    users.forEach(function(user) {
                        html += `
                            <div class="search-result" data-id="${user.id}" data-name="${user.display_name}" data-avatar="${user.avatar_url || ''}" ${user.simezu_id ? 'data-simezu-user-id="' + user.simezu_id + '"' : ''}>
                                <img src="${user.avatar_url || simuzeCommunity.defaultAvatar}" 
                                     alt=""
                                     ${user.simezu_id ? 'data-simezu-user-id="' + user.simezu_id + '"' : ''}>
                                <div>
                                    <div class="search-name">${user.display_name}</div>
                                    <div class="search-handle">@${user.username}</div>
                                </div>
                            </div>
                        `;
                    });
                    $('#recipient-dropdown').html(html || '<div class="search-result">No users found</div>').addClass('show');
                }
            });
        }, 300);
    });
    
    $(document).on('click', '.search-result[data-id]', function() {
        selectedRecipient = {
            id: $(this).data('id'),
            name: $(this).data('name'),
            avatar: $(this).data('avatar'),
            simezu_id: $(this).data('simezu-user-id') || $(this).attr('data-simezu-user-id') || ''
        };
        
        $('#selected-recipient').html(`
            <img src="${selectedRecipient.avatar || simuzeCommunity.defaultAvatar}" 
                 alt=""
                 ${selectedRecipient.simezu_id ? 'data-simezu-user-id="' + selectedRecipient.simezu_id + '"' : ''}>
            <span>${selectedRecipient.name}</span>
            <button type="button" class="remove-recipient">&times;</button>
        `).show();
        
        $('#recipient-search').val('').hide();
        $('#recipient-dropdown').removeClass('show');
    });
    
    $(document).on('click', '.remove-recipient', function() {
        selectedRecipient = null;
        $('#selected-recipient').hide();
        $('#recipient-search').show().focus();
    });
    
    // Send new message
    $('#send-new-message').on('click', function() {
        if (!selectedRecipient) {
            if (typeof simuzeAlertError === 'function') {
                simuzeAlertError('Please select a recipient');
            } else {
                alert('Please select a recipient');
            }
            return;
        }
        
        const message = $('#new-message-input').html().trim();
        if (!message || message === '<br>') {
            if (typeof simuzeAlertError === 'function') {
                simuzeAlertError('Please enter a message');
            } else {
                alert('Please enter a message');
            }
            return;
        }
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'messages',
            type: 'POST',
            data: JSON.stringify({
                recipient_id: selectedRecipient.id,
                message: message
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function(response) {
                // Redirect to the thread
                if (response.thread_id) {
                    window.location.href = window.location.pathname + '?tab=messages&thread=' + response.thread_id;
                } else {
                    location.reload();
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to send message';
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError(errorMsg);
                } else {
                    alert(errorMsg);
                }
            }
        });
    });
    
    // @ Mentions
    let mentionSearch = '';
    let mentionStart = -1;
    
    $(document).on('input', '[data-mention-enabled]', function() {
        const $input = $(this);
        const text = $input.text();
        const selection = window.getSelection();
        const cursorPos = selection.anchorOffset;
        
        // Check for @ trigger
        const beforeCursor = text.substring(0, cursorPos);
        const atIndex = beforeCursor.lastIndexOf('@');
        
        if (atIndex >= 0 && (atIndex === 0 || beforeCursor[atIndex - 1] === ' ')) {
            mentionSearch = beforeCursor.substring(atIndex + 1);
            mentionStart = atIndex;
            
            if (mentionSearch.length >= 1) {
                searchMentions(mentionSearch, $input);
            } else {
                $('#mentions-dropdown').hide();
            }
        } else {
            $('#mentions-dropdown').hide();
        }
    });
    
    function searchMentions(query, $input) {
        $.ajax({
            url: simuzeCommunity.apiUrl + 'search/users',
            type: 'GET',
            data: { query: query },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function(users) {
                if (users.length === 0) {
                    $('#mentions-dropdown').hide();
                    return;
                }
                
                let html = '';
                users.forEach(function(user, index) {
                    html += `
                        <div class="mention-item ${index === 0 ? 'active' : ''}" data-id="${user.id}" data-username="${user.username}" data-name="${user.display_name}" ${user.simezu_id ? 'data-simezu-user-id="' + user.simezu_id + '"' : ''}>
                            <img src="${user.avatar_url || simuzeCommunity.defaultAvatar}" 
                                 alt=""
                                 ${user.simezu_id ? 'data-simezu-user-id="' + user.simezu_id + '"' : ''}>
                            <div>
                                <div class="mention-name">${user.display_name}</div>
                                <div class="mention-handle">@${user.username}</div>
                            </div>
                        </div>
                    `;
                });
                
                const rect = $input[0].getBoundingClientRect();
                $('#mentions-dropdown')
                    .html(html)
                    .css({
                        top: rect.bottom + window.scrollY + 5,
                        left: rect.left + window.scrollX
                    })
                    .show();
            }
        });
    }
    
    $(document).on('click', '.mention-item', function() {
        const username = $(this).data('username');
        const $input = $('[data-mention-enabled]:focus');
        
        if ($input.length) {
            const text = $input.text();
            const newText = text.substring(0, mentionStart) + '@' + username + ' ' + text.substring(mentionStart + mentionSearch.length + 1);
            $input.html(newText.replace(/@(\w+)/g, '<span class="mention">@$1</span>'));
            
            // Move cursor to end
            const range = document.createRange();
            const sel = window.getSelection();
            range.selectNodeContents($input[0]);
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
        }
        
        $('#mentions-dropdown').hide();
    });
    
    // Close dropdowns on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-container').length) {
            $('#recipient-dropdown').removeClass('show');
        }
        if (!$(e.target).closest('[data-mention-enabled], #mentions-dropdown').length) {
            $('#mentions-dropdown').hide();
        }
    });
});
</script>
