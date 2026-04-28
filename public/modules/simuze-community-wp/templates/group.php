<?php
/**
 * Group Template - Matching user profile page style
 * Supports: /group/{slug}/
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure $group is available
if (!isset($group) || !$group) {
    echo '<div class="simuze-error"><p>Group not found.</p></div>';
    return;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_owner = $current_user && ($group->creator_id === $current_user->id);
$is_member = $current_user && ($group->is_member($current_user->id) || $is_owner);
$is_admin = $current_user && ($is_owner || $group->is_member($current_user->id, 'admin'));
$is_following = $current_user && class_exists('Simuze_Follow') ? Simuze_Follow::is_following($current_user->id, 'group', $group->id) : false;

// Get group type
$group_type_data = $group->get_group_type();
$group_type_slug = $group_type_data ? $group_type_data['slug'] : null;

// Get tab from query var (clean URL) or fall back to GET parameter
$tab = get_query_var('simuze_group_tab');
if (empty($tab)) {
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
}

// Determine the base URL for navigation links
$group_base_url = $group->get_url();

// Get display name and avatar
$display_name = $group->name;
// Get large avatar URL (full size for the large dashboard avatar)
// This method handles caching, size variants, and fallback to default
$avatar_url = $group->get_avatar_url('full');

// Define navigation items based on membership and group type
$nav_items = array();

// For MEMBERS/OWNERS (logged in and member or creator of group)
if ($is_member && $current_user) {
    // All groups get these base items
    $nav_items['dashboard'] = 'Dashboard';
    
    // Group type specific items
    if ($group_type_slug === 'artist') {
        // Artist groups: Dashboard, Music, Members, Activity, Settings
        $nav_items['music'] = 'Music';
    } elseif ($group_type_slug === 'label') {
        // Label groups: Dashboard, Artists, Members, Activity, Settings
        $nav_items['artists'] = 'Artists';
    }
    
    // Common items for all member groups
    $nav_items['members'] = 'Members';
    $nav_items['activity'] = 'Activity';
    
    // Settings only for admins
    if ($is_admin) {
        $nav_items['settings'] = 'Settings';
    }
    
    // Default tab for members
    if (empty($tab)) {
        $tab = 'dashboard';
    }
} else {
    // For NON-MEMBERS (public view)
    // Location-based groups (Country, Province, City) show Overview
    $location_types = array('country', 'province', 'city');
    if (in_array($group_type_slug, $location_types)) {
        $nav_items['overview'] = 'Overview';
        if (empty($tab)) {
            $tab = 'overview';
        }
    } else {
        // All other groups show Profile, Music (for Artists), and Activity
        $nav_items['profile'] = 'Profile';
        
        // Add Music for Artist groups
        if ($group_type_slug === 'artist') {
            $nav_items['music'] = 'Music';
        }
        
        $nav_items['activity'] = 'Activity';
        if (empty($tab)) {
            $tab = 'profile';
        }
    }
}
?>

<!-- Full-width dashboard header -->
<div class="col-fullwidth">
    <div class="header-image">
        <?php if (!empty($group->header_url)): ?>
            <div class="header-image-bg" style="background-image: url('<?php echo esc_url($group->header_url); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;"></div>
        <?php else: ?>
            <div class="header-image-bg"></div>
        <?php endif; ?>
        <div class="header-text">
            <h1><?php echo esc_html($display_name); ?></h1>
        </div>
    </div>
</div>

<section class="fullwidth">

<!-- Avatar positioned absolutely within header-image -->
<div class="dashboard-user-avatar-large">
    <img src="<?php echo esc_url($avatar_url); ?>" 
         alt="<?php echo esc_attr($display_name); ?>" 
         class="avatar-large" 
         width="240" 
         height="240"
         onerror="this.onerror=null; this.src='<?php echo esc_js(get_template_directory_uri() . '/images/default-group.svg'); ?>';">
</div>

<!-- Basic Sticky Example -->
<div class="row no-gutters">
    <div class="col col-2">
        <div class="sticky-container" data-sticky-container="group-dashboard">
            <div class="sticky-parent" style="height: 100%;">
                <div class="sticky-scroll">
                    <?php if ($is_member && $current_user): ?>
                        <h3><?php echo esc_html($display_name); ?></h3>
                    <?php endif; ?>
                    
                    <ul class="dashboard-nav">
                        <?php foreach ($nav_items as $nav_key => $nav_label): ?>
                            <li class="<?php echo $tab === $nav_key ? 'current' : ''; ?>">
                                <?php 
                                // Generate clean URL: /group/{handle}/{tab}/
                                // Dashboard is the default, so no tab in URL for dashboard
                                if ($nav_key === 'dashboard') {
                                    $nav_url = $group_base_url;
                                } else {
                                    $nav_url = trailingslashit($group_base_url) . $nav_key . '/';
                                }
                                ?>
                                <a href="<?php echo esc_url($nav_url); ?>"><?php echo esc_html($nav_label); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                   
                </div>
            </div>
        </div>
    </div>
    
    <div class="col col-10">
        <div class="row scroll-area full-width-content">
            <?php
            // Route to appropriate template based on membership and tab
            if ($is_member && $current_user) {
                // MEMBER VIEW - Show member-specific pages
                switch ($tab) {
                    case 'dashboard':
                        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-dashboard.php';
                        break;
                    case 'music':
                        // Only Artist groups can access Music page
                        if ($group_type_slug === 'artist') {
                            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-music.php';
                        } else {
                            echo '<div class="access-denied"><p>This page is only available for Artist groups.</p></div>';
                        }
                        break;
                    case 'artists':
                        // Only Label groups can access Artists page
                        if ($group_type_slug === 'label') {
                            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-artists.php';
                        } else {
                            echo '<div class="access-denied"><p>This page is only available for Label groups.</p></div>';
                        }
                        break;
                    case 'members':
                        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-members.php';
                        break;
                    case 'activity':
                        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-activity.php';
                        break;
                    case 'settings':
                        if ($is_admin) {
                            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-settings.php';
                        } else {
                            echo '<div class="access-denied"><p>Access denied. Only group administrators can access settings.</p></div>';
                        }
                        break;
                    default:
                        // Default: show dashboard
                        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-dashboard.php';
                }
            } else {
                // NON-MEMBER VIEW (PUBLIC) - Show public pages
                $location_types = array('country', 'province', 'city');
                if (in_array($group_type_slug, $location_types)) {
                    // For location-based groups, defer to their dedicated templates
                    // Here we show the public profile summary as a safe fallback.
                    include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-public.php';
                } else {
                    // Other groups show Profile, Music (for Artists), and Activity
                    $group_type_data = $group->get_group_type();
                    $group_type_slug = $group_type_data ? $group_type_data['slug'] : null;
                    
                    switch ($tab) {
                        case 'profile':
                            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-public.php';
                            break;
                        case 'music':
                            // Only Artist groups can access Music page (public)
                            if ($group_type_slug === 'artist') {
                                include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-music-public.php';
                            } else {
                                echo '<div class="access-denied"><p>This page is only available for Artist groups.</p></div>';
                            }
                            break;
                        case 'activity':
                            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-activity-public.php';
                            break;
                        default:
                            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group-public.php';
                    }
                }
            }
            ?>
        </div>
    </div>
</div>
</section>

