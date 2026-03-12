<?php
if (!class_exists('Simuze_Admin')) {
    echo 'Simuze Community module not loaded.';
    return;
}
Simuze_Admin::get_instance()->render_reports();
