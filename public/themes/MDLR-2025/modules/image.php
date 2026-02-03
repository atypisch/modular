<?php
/**
 * Image block. $block['data']['id'] = media ID. Resolve URL in theme or via API.
 */
if (!isset($block)) {
    return;
}
$id = isset($block['data']['id']) ? (int) $block['data']['id'] : 0;
if ($id <= 0) {
    return;
}
$name = $block['name'] ?? '';
$url = ''; // Theme or app can set media base URL and resolve id -> path
if (function_exists('get_media_url')) {
    $url = get_media_url($id);
} else {
    $url = '/modular/admin/api/index.php/api/v1/media/' . $id;
}
?>
<div class="block block-image block-<?php echo htmlspecialchars($name); ?>">
    <img src="<?php echo htmlspecialchars($url); ?>" alt="" loading="lazy">
</div>
