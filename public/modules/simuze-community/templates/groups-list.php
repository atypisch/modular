<?php
/**
 * Groups List Template
 */

if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN') && !defined('MODULAR_PATH')) {
    exit;
}

$group_type = !empty($atts['group_type']) ? $atts['group_type'] : null;
$per_page = !empty($atts['per_page']) ? (int) $atts['per_page'] : 20;

$result = Simuze_Group::get_all(array(
    'group_type' => $group_type,
    'per_page' => $per_page,
    'page' => 1,
));

$groups = $result['groups'];
?>

<div class="simuze-groups-list">
    <h2>Groups</h2>
    
    <?php if (!empty($groups)): ?>
        <ul class="groups-grid">
            <?php foreach ($groups as $group): ?>
                <li class="group-card">
                    <a href="<?php echo esc_url($group->get_url()); ?>">
                        <?php if ($group->avatar_url): ?>
                            <img src="<?php echo esc_url($group->avatar_url); ?>" alt="<?php echo esc_attr($group->name); ?>" class="group-avatar">
                        <?php endif; ?>
                        <h3><?php echo esc_html($group->name); ?></h3>
                        <?php if ($group->description): ?>
                            <p class="group-description"><?php echo esc_html(wp_trim_words($group->description, 20)); ?></p>
                        <?php endif; ?>
                        <span class="group-meta"><?php echo esc_html($group->get_member_count()); ?> members</span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No groups found.</p>
    <?php endif; ?>
</div>











