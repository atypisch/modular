<?php
/** Single template for post type: playlist. Reuses single.php. */
if (!isset($post)) $post = [];
$post['_template_type'] = 'playlist';
include __DIR__ . '/single.php';
