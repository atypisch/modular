<?php
/** Single template for post type: event. Reuses single.php. */
if (!isset($post)) $post = [];
$post['_template_type'] = 'event';
include __DIR__ . '/single.php';
