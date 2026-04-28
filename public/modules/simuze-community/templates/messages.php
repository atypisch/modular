<?php
/**
 * Messages Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();

if (!$current_user) {
    echo '<p>Please log in to view messages.</p>';
    return;
}

$thread_id = isset($_GET['thread']) ? sanitize_text_field($_GET['thread']) : null;
$threads = Simuze_Message::get_threads($current_user->id);

if ($thread_id) {
    $messages = Simuze_Message::get_user_messages($current_user->id, array('thread_id' => $thread_id));
} else {
    $messages = array();
}
?>

<div class="simuze-messages">
    <div class="messages-sidebar">
        <h2>Conversations</h2>
        <?php if (!empty($threads)): ?>
            <ul class="threads-list">
                <?php foreach ($threads as $thread): ?>
                    <li class="thread-item <?php echo $thread_id === $thread['thread_id'] ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(md_home_url('/messages/?thread=' . $thread['thread_id'])); ?>">
                            <span class="thread-id">Thread #<?php echo esc_html($thread['thread_id']); ?></span>
                            <?php if ($thread['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo esc_html($thread['unread_count']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No conversations yet.</p>
        <?php endif; ?>
    </div>
    
    <div class="messages-content">
        <?php if ($thread_id && !empty($messages)): ?>
            <div class="messages-list">
                <?php foreach ($messages as $message): ?>
                    <div class="message-item <?php echo $message['sender_id'] == $current_user->id ? 'sent' : 'received'; ?>">
                        <div class="message-content">
                            <?php echo esc_html($message['message']); ?>
                        </div>
                        <div class="message-meta">
                            <?php echo esc_html(human_time_diff(strtotime($message['created_at']), current_time('timestamp')) . ' ago'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <form class="send-message-form">
                <input type="hidden" name="thread_id" value="<?php echo esc_attr($thread_id); ?>">
                <textarea name="message" placeholder="Type your message..." required></textarea>
                <button type="submit" class="button">Send</button>
            </form>
        <?php else: ?>
            <p>Select a conversation to view messages.</p>
        <?php endif; ?>
    </div>
</div>











