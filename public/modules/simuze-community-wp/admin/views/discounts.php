<?php
/**
 * Discounts Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simuze_discount_nonce'])) {
    if (!wp_verify_nonce($_POST['simuze_discount_nonce'], 'simuze_discount_action')) {
        wp_die('Security check failed');
    }
    
    $action = sanitize_text_field($_POST['discount_action'] ?? '');
    
    if ($action === 'create') {
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'code' => sanitize_text_field($_POST['code']),
            'target_type' => sanitize_text_field($_POST['target_type']),
            'target_id' => !empty($_POST['target_id']) ? intval($_POST['target_id']) : null,
            'product_type' => sanitize_text_field($_POST['product_type']),
            'discount_type' => sanitize_text_field($_POST['discount_type']),
            'discount_value' => floatval($_POST['discount_value']),
            'min_purchase' => !empty($_POST['min_purchase']) ? floatval($_POST['min_purchase']) : null,
            'max_uses' => !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null,
            'active_from' => !empty($_POST['active_from']) ? sanitize_text_field($_POST['active_from']) : null,
            'active_until' => !empty($_POST['active_until']) ? sanitize_text_field($_POST['active_until']) : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        );
        
        $discount = Simuze_Discount::create($data);
        
        if ($discount) {
            echo '<div class="notice notice-success"><p>Discount created successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Failed to create discount.</p></div>';
        }
    } elseif ($action === 'delete' && !empty($_POST['discount_id'])) {
        $discount = Simuze_Discount::get(intval($_POST['discount_id']));
        if ($discount) {
            $discount->delete();
            echo '<div class="notice notice-success"><p>Discount deleted.</p></div>';
        }
    } elseif ($action === 'toggle' && !empty($_POST['discount_id'])) {
        $discount = Simuze_Discount::get(intval($_POST['discount_id']));
        if ($discount) {
            $discount->update(array('is_active' => $discount->is_active ? 0 : 1));
            echo '<div class="notice notice-success"><p>Discount status updated.</p></div>';
        }
    }
}

// Get all discounts
$discounts = Simuze_Discount::get_all(array('limit' => 100));
?>

<div class="wrap">
    <h1>Manage Discounts</h1>
    
    <!-- Add New Discount Form -->
    <div class="discount-form-container" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
        <h2>Add New Discount</h2>
        <form method="post">
            <?php wp_nonce_field('simuze_discount_action', 'simuze_discount_nonce'); ?>
            <input type="hidden" name="discount_action" value="create">
            
            <table class="form-table">
                <tr>
                    <th><label for="name">Discount Name *</label></th>
                    <td><input type="text" name="name" id="name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="code">Discount Code</label></th>
                    <td>
                        <input type="text" name="code" id="code" class="regular-text" style="text-transform: uppercase;">
                        <p class="description">Leave empty for automatic discounts (no code needed)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="target_type">Target Type *</label></th>
                    <td>
                        <select name="target_type" id="target_type">
                            <option value="global">Global (All Products)</option>
                            <option value="group">Specific Group/Artist</option>
                            <option value="product">Specific Product</option>
                        </select>
                    </td>
                </tr>
                <tr class="target-id-row" style="display: none;">
                    <th><label for="target_id">Target ID</label></th>
                    <td><input type="number" name="target_id" id="target_id" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="product_type">Product Type</label></th>
                    <td>
                        <select name="product_type" id="product_type">
                            <option value="all">All Products</option>
                            <option value="song">Songs Only</option>
                            <option value="album">Albums Only</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="discount_type">Discount Type *</label></th>
                    <td>
                        <select name="discount_type" id="discount_type">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (€)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="discount_value">Discount Value *</label></th>
                    <td><input type="number" name="discount_value" id="discount_value" class="small-text" step="0.01" required></td>
                </tr>
                <tr>
                    <th><label for="min_purchase">Minimum Purchase (€)</label></th>
                    <td><input type="number" name="min_purchase" id="min_purchase" class="small-text" step="0.01"></td>
                </tr>
                <tr>
                    <th><label for="max_uses">Maximum Uses</label></th>
                    <td>
                        <input type="number" name="max_uses" id="max_uses" class="small-text">
                        <p class="description">Leave empty for unlimited uses</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="active_from">Active From</label></th>
                    <td><input type="datetime-local" name="active_from" id="active_from"></td>
                </tr>
                <tr>
                    <th><label for="active_until">Active Until</label></th>
                    <td><input type="datetime-local" name="active_until" id="active_until"></td>
                </tr>
                <tr>
                    <th><label for="is_active">Status</label></th>
                    <td><label><input type="checkbox" name="is_active" id="is_active" value="1" checked> Active</label></td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">Create Discount</button>
            </p>
        </form>
    </div>
    
    <!-- Existing Discounts -->
    <h2>Existing Discounts</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Type</th>
                <th>Value</th>
                <th>Target</th>
                <th>Uses</th>
                <th>Valid Period</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($discounts)): ?>
                <tr><td colspan="9">No discounts found.</td></tr>
            <?php else: ?>
                <?php foreach ($discounts as $discount): ?>
                    <tr>
                        <td><strong><?php echo esc_html($discount->name); ?></strong></td>
                        <td><?php echo $discount->code ? '<code>' . esc_html($discount->code) . '</code>' : '<em>Auto</em>'; ?></td>
                        <td><?php echo esc_html(ucfirst($discount->discount_type)); ?></td>
                        <td><?php echo esc_html($discount->get_display_value()); ?></td>
                        <td><?php echo esc_html(ucfirst($discount->target_type)); ?><?php echo $discount->target_id ? ' #' . $discount->target_id : ''; ?></td>
                        <td><?php echo esc_html($discount->uses_count); ?><?php echo $discount->max_uses ? '/' . $discount->max_uses : ''; ?></td>
                        <td>
                            <?php if ($discount->active_from || $discount->active_until): ?>
                                <?php echo $discount->active_from ? date('M j', strtotime($discount->active_from)) : '∞'; ?>
                                -
                                <?php echo $discount->active_until ? date('M j', strtotime($discount->active_until)) : '∞'; ?>
                            <?php else: ?>
                                Always
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $discount->is_active && $discount->is_valid() ? 'active' : 'inactive'; ?>">
                                <?php echo $discount->is_active && $discount->is_valid() ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('simuze_discount_action', 'simuze_discount_nonce'); ?>
                                <input type="hidden" name="discount_action" value="toggle">
                                <input type="hidden" name="discount_id" value="<?php echo esc_attr($discount->id); ?>">
                                <button type="submit" class="button button-small"><?php echo $discount->is_active ? 'Disable' : 'Enable'; ?></button>
                            </form>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Delete this discount?');">
                                <?php wp_nonce_field('simuze_discount_action', 'simuze_discount_nonce'); ?>
                                <input type="hidden" name="discount_action" value="delete">
                                <input type="hidden" name="discount_id" value="<?php echo esc_attr($discount->id); ?>">
                                <button type="submit" class="button button-small button-link-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}
.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #f8d7da; color: #721c24; }
</style>

<script>
document.getElementById('target_type').addEventListener('change', function() {
    document.querySelector('.target-id-row').style.display = this.value !== 'global' ? '' : 'none';
});
</script>











