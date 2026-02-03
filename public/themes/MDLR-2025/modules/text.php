<?php
/**
 * Text block. $block['data']['text']
 */
if (!isset($block)) {
    return;
}
$text = isset($block['data']['text']) ? (string) $block['data']['text'] : '';
if ($text === '') {
    return;
}
$name = $block['name'] ?? '';
?>
<div class="block block-text block-<?php echo htmlspecialchars($name); ?>">
    <?php echo nl2br(htmlspecialchars($text)); ?>
</div>
