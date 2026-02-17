<?php
/** Single template for post type: album. Reuses single.php. */
if (!isset($post)) $post = [];
$post['_template_type'] = 'album';
include __DIR__ . '/single.php';
