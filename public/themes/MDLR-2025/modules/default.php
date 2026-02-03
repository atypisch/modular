<?php
/**
 * Fallback block module when type-specific module is missing.
 * $block: ['type' => string, 'name' => string, 'data' => array|object]
 */
if (!isset($block)) {
    return;
}
$data = $block['data'] ?? [];
$name = $block['name'] ?? '';
$type = $block['type'] ?? 'unknown';
?>
<div class="block block-<?php echo htmlspecialchars($type); ?> block-<?php echo htmlspecialchars($name); ?>" data-block-type="<?php echo htmlspecialchars($type); ?>">
    <?php if (!empty($data)): ?>
    <pre class="block-raw"><?php echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
    <?php endif; ?>
</div>
