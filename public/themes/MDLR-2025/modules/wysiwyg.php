<?php
/**
 * WYSIWYG block. $block['data']['html']
 */
if (!isset($block)) {
    return;
}
$html = isset($block['data']['html']) ? (string) $block['data']['html'] : '';
if ($html === '') {
    return;
}
$name = $block['name'] ?? '';
?>
<div class="block block-wysiwyg block-<?php echo htmlspecialchars($name); ?>">
    <?php echo $html; ?>
</div>
