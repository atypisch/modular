<?php
/** Single template for post type: track. Reuses single.php. */
if (!isset($post)) $post = [];
$post['_template_type'] = 'track';
include __DIR__ . '/single.php';
