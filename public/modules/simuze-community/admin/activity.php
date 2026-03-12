<?php
if (!class_exists('Simuze_Admin')) {
    echo 'Simuze Community module not loaded.';
    return;
}
if (method_exists(Simuze_Admin::get_instance(), 'render_activity')) {
    Simuze_Admin::get_instance()->render_activity();
} else {
    echo '<div class="p-4"><h2>Activity</h2><p>Community activity log.</p></div>';
}
