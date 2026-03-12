<?php
if (!class_exists('Simuze_Admin')) {
    echo 'Simuze Community module not loaded.';
    return;
}
if (method_exists(Simuze_Admin::get_instance(), 'render_messages')) {
    Simuze_Admin::get_instance()->render_messages();
} else {
    echo '<div class="p-4"><h2>Messages</h2><p>Message moderation and inbox management.</p></div>';
}
