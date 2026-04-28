<?php
/**
 * Group Discography Template
 * Uses existing Simuze discography functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

// This will use the existing discography system
// The discography data should be linked to the group_id
$group_id = $group->id;

// Render basic placeholder; Simuze Community provides group data
echo '<div class="group-discography">';
echo '<h2>Discography</h2>';
echo '<p>Discography content will be displayed here.</p>';
echo '</div>';
?>










