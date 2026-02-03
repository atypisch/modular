<?php
/**
 * URL block. $block['data']['url']
 */
if (!isset($block)) {
    return;
}
$url = isset($block['data']['url']) ? (string) $block['data']['url'] : '';
if ($url === '') {
    return;
}
$name = $block['name'] ?? '';
?>
<div class="block block-url block-<?php echo htmlspecialchars($name); ?>">
    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($url); ?></a>
</div>
