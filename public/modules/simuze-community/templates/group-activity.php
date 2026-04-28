<?php
/**
 * Group Activity Template
 */

if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN') && !defined('MODULAR_PATH')) {
    exit;
}

$activity = Simuze_Activity::get_group_feed($group->id, array('per_page' => 20));
?>

<div class="group-activity">
    <h2>Activity</h2>
    
    <?php if (!empty($activity)): ?>
        <ul class="activity-list">
            <?php foreach ($activity as $item): ?>
                <li class="activity-item">
                    <div class="activity-content">
                        <?php echo esc_html($item['content']); ?>
                    </div>
                    <div class="activity-meta">
                        <?php echo esc_html(human_time_diff(strtotime($item['created_at']), current_time('timestamp')) . ' ago'); ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No activity yet.</p>
    <?php endif; ?>
</div>











