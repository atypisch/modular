<?php
/**
 * Public Group Activity Template (non-member view)
 * Shows recent public activity for the group.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Reuse the standard group-activity template — it already handles empty states
include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-activity.php';
