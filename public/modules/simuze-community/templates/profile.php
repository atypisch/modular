<?php
/**
 * User Profile Template - Matching playlist page style
 * Supports: /profile/, /profile/?user={simezu_id}, /user/{username}/
 */

if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
    exit;
}

// Some Modular execution paths render templates without the module bootstrap running first.
// Ensure the template include base path always exists to prevent fatal errors.
if (!defined('SIMUZE_COMMUNITY_PLUGIN_DIR')) {
    define('SIMUZE_COMMUNITY_PLUGIN_DIR', rtrim(dirname(__DIR__), '/') . '/');
}

// Ensure $user is available
if (!isset($user) || !$user) {
    echo '<div class="simuze-error"><p>User not found.</p></div>';
    return;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_own_profile = $current_user && (int) $current_user->id === (int) $user->id;

// Modular + Simezu SSO: if Simuze_Auth did not resolve a local user match,
// fall back to the Simezu JWT context populated in $GLOBALS['current_user'].
// We treat the profile as "own" when the Simezu user (by simezu_id/email)
// matches the Simuze_User backing this profile.
if (!$is_own_profile && isset($GLOBALS['current_user']) && is_object($GLOBALS['current_user'])) {
    $jwt_user = $GLOBALS['current_user'];
    $jwt_simezu_id = $jwt_user->simezu_id ?? ($jwt_user->id ?? null);
    $jwt_email     = $jwt_user->email ?? null;

    if (!empty($jwt_simezu_id) && !empty($user->simezu_id) && $jwt_simezu_id === $user->simezu_id) {
        $is_own_profile = true;
    } elseif (!empty($jwt_email) && !empty($user->email) && $jwt_email === $user->email) {
        $is_own_profile = true;
    }
}

// Temporary debug to understand own/public detection in Modular Simezu integration.
// Appears only in HTML comments; safe to leave during development.
echo "\n<!-- simuze-profile-debug "
   . "is_own_profile=" . ($is_own_profile ? '1' : '0')
   . " current_user_id=" . ($current_user->id ?? 'null')
   . " current_user_simezu=" . ($current_user->simezu_id ?? 'null')
   . " current_user_email=" . ($current_user->email ?? 'null')
   . " profile_id=" . ($user->id ?? 'null')
   . " profile_simezu=" . ($user->simezu_id ?? 'null')
   . " profile_email=" . ($user->email ?? 'null')
   . " -->\n";

// Get tab from query var (clean URL) or from GET parameter (legacy)
$tab = get_query_var('simuze_tab');
if (empty($tab)) {
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
}

// Determine the base URL for navigation links
// Always use the user's unique URL (/user/{username}/)
$profile_base_url = $user->get_url();

// Get display name and avatar
$display_name = $user->display_name ?: $user->username;
// Use new avatar system with caching - get full size for dashboard
// If simezu_id exists, use Simuze_Avatar class directly for better performance
if (!empty($user->simezu_id) && class_exists('Simuze_Avatar')) {
    $avatar = Simuze_Avatar::get_instance();
    $avatar_url = $avatar->get_user_avatar_url($user->simezu_id, 'full', array('cache_only' => false));

    // Fallback: if avatar_url is default/placeholder, go via Modular Simezu auth proxy
    if (empty($avatar_url) || strpos($avatar_url, 'default-avatar') !== false || strpos($avatar_url, 'data:image') !== false) {
        $api_url = rtrim(md_home_url('/api/auth'), '/');
        $avatar_url = $api_url . '/users/' . urlencode($user->simezu_id) . '/avatar/image?size=240';
    }
} else {
    // No Simezu id, fall back to local default
    $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
    $avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : theme_uri('/images/profile_default.jpg');
}

// Define navigation items (no icons, matching playlist style)
// For own profile we show the full dashboard; for public view we only show
// the public tabs and rename Dashboard -> Profile.
if ($is_own_profile) {
    $nav_items = array(
        'dashboard' => 'Dashboard',
        'playlists' => 'Playlists',
        'shows'     => 'Shows',
        'activity'  => 'Activity',
        'messages'  => 'Messages',
        'friends'   => 'Friends',
        'groups'    => 'Groups',
    );

    // Private-only items
    $nav_items['create-group']     = 'Create Group';
    $nav_items['purchases']        = 'Purchases';
    $nav_items['settings']         = 'Account Settings';
    $nav_items['payment-settings'] = 'Payment Settings';
} else {
    // Public view: hide Messages/Friends, show Profile + limited tabs
    $nav_items = array(
        'profile'   => 'Profile',
        'playlists' => 'Playlists',
        'shows'     => 'Shows',
        'groups'    => 'Groups',
    );
}
?>

<!-- Full-width dashboard header -->
<div class="col-fullwidth">
    <div class="header-image">
        <?php if (!empty($user->header_url)): ?>
            <div class="header-image-bg" style="background-image: url('<?php echo esc_url($user->header_url); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;"></div>
        <?php else: ?>
            <div class="header-image-bg"></div>
        <?php endif; ?>
        <?php
            // Do not show fallback email/username; keep empty until Simezu fetch fills it
            $full_name = '';
            $unique_handle = '';
        ?>
        <div class="header-user-meta" id="header-user-meta" style="opacity:0;">
            <div class="header-user-name" id="header-user-name"></div>
            <div class="header-user-handle" id="header-user-handle"></div>
        </div>
        <?php if ($current_user && !$is_own_profile): ?>
            <?php
            $is_following = class_exists('Simuze_Follow') ? Simuze_Follow::is_following($current_user->id, 'user', $user->id) : false;
            $is_friend    = class_exists('Simuze_Friend') ? Simuze_Friend::are_friends($current_user->id, $user->id) : false;
            ?>
            <div class="profile-header-actions">
                <button class="button button-secondary btn-follow-user"
                        data-user-id="<?php echo esc_attr($user->id); ?>"
                        data-action="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                    <?php echo $is_following ? 'Following' : 'Follow'; ?>
                </button>
                <?php if (!$is_friend): ?>
                    <button class="button button-secondary btn-add-friend"
                            data-user-id="<?php echo esc_attr($user->id); ?>">
                        Add Friend
                    </button>
                <?php else: ?>
                    <span class="badge badge-success">Friends</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<section class="fullwidth">

<!-- Avatar positioned absolutely within header-image -->
<div class="dashboard-user-avatar-large">
    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" 
         class="avatar-large" 
         data-simezu-user-id="<?php echo esc_attr($user->simezu_id ?? ''); ?>"
         width="240" height="240">
</div>

<script>
(function() {
    const simezuId = '<?php echo esc_js($user->simezu_id ?? ''); ?>';
    const nameEl = document.getElementById('header-user-name');
    const handleEl = document.getElementById('header-user-handle');
    const sideNameEl = document.getElementById('side-user-name');
    const headerMeta = document.getElementById('header-user-meta');

    if (!simezuId) return;

    function applyProfile(data) {
        if (!data) return;
        const first = data.first_name || '';
        const last = data.last_name || '';
        const full = (first + ' ' + last).trim() || data.display_name || data.username || '';
        if (full) {
            if (nameEl) nameEl.textContent = full;
            if (sideNameEl) sideNameEl.textContent = full;
            if (headerMeta) headerMeta.style.opacity = '1';
            if (sideNameEl) sideNameEl.style.opacity = '1';
        }
        const uid = data.unique_identifier || data.username;
        if (uid && handleEl) handleEl.textContent = '@' + uid;
    }

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    function getAccessToken() {
        try {
            if (typeof SimezuAuth !== 'undefined') {
                if (typeof SimezuAuth.getToken === 'function') {
                    var t = SimezuAuth.getToken();
                    if (t) return t;
                }
                if (typeof SimezuAuth.getAccessToken === 'function') {
                    var t2 = SimezuAuth.getAccessToken();
                    if (t2) return t2;
                }
            }
        } catch (e) {}
        return getCookie('accessToken') ||
               getCookie('simezu_token') ||
               window.localStorage.getItem('simezu_token') ||
               null;
    }

    function trySimezuAuthThenFetch(retries) {
        retries = retries || 0;
        if (typeof SimezuAuth !== 'undefined' && typeof SimezuAuth.getUser === 'function') {
            SimezuAuth.getUser().then(function(user) {
                if (user && (!simezuId || user.id === simezuId)) {
                    applyProfile(user);
                } else {
                    fetchProfile();
                }
            }).catch(fetchProfile);
        } else if (retries < 20) {
            setTimeout(function() { trySimezuAuthThenFetch(retries + 1); }, 100);
        } else {
            fetchProfile();
        }
    }

    // Try SimezuAuth first with retry
    trySimezuAuthThenFetch(0);

    function fetchProfile() {
        // Requires bearer; use SimezuAuth token helper if available
        let token = getAccessToken();
        if (!token) return;
        fetch('/simezu/api/auth/users/' + encodeURIComponent(simezuId), {
            headers: { 'Authorization': 'Bearer ' + token },
            credentials: 'include'
        }).then(function(r) {
            if (!r.ok) return;
            return r.json();
        }).then(applyProfile).catch(function(){});
    }
})();
</script>

<!-- Basic Sticky Example -->
<div class="row no-gutters">
    <div class="col col-2">
        <div class="sticky-container" data-sticky-container="home-dashboard">
            <div class="sticky-parent" style="height: 100%;">
                <div class="sticky-scroll">
                    <h3 id="side-user-name" style="opacity:0;"><?php echo esc_html($full_name); ?></h3>
                    
                    <ul class="dashboard-nav">
                        <?php foreach ($nav_items as $nav_key => $nav_label): ?>
                            <li class="<?php echo $tab === $nav_key ? 'current' : ''; ?>">
                                <?php 
                                // Generate clean URL: /user/{username}/{tab}/
                                // Dashboard is the default, so no tab in URL for dashboard
                                if ($nav_key === 'dashboard') {
                                    $nav_url = $profile_base_url;
                                } else {
                                    $nav_url = trailingslashit($profile_base_url) . $nav_key . '/';
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
            // Routing:
            // - For own profile: show full dashboard tabs (dashboard, playlists, activity, etc.)
            // - For public view (other users): always show the public profile template,
            //   but we still respect the selected tab label in the sidebar.
            if (!$is_own_profile) {
                // Public profile: allow playlist listing/detail routes, otherwise keep public profile view.
                switch ($tab) {
                    case 'playlists':
                        $playlist_slug = get_query_var('simuze_playlist_slug');
                        if ($playlist_slug) {
                            include get_template_directory() . '/single-playlist.php';
                        } else {
                            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-playlists.php';
                        }
                        break;
                    case 'shows':
                        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-shows.php';
                        break;
                    case 'profile':
                    case 'dashboard':
                    case '':
                    default:
                        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-public.php';
                        break;
                }
            } else {
                // Show dashboard for own profile (default)
                switch ($tab) {
                    case 'dashboard':
                    case '':
                        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-dashboard.php';
                        break;
                    case 'playlists':
                        $playlist_slug = get_query_var('simuze_playlist_slug');
                        if ($playlist_slug) {
                            include get_template_directory() . '/single-playlist.php';
                        } else {
                            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-playlists.php';
                        }
                        break;
                case 'upload':
                    // Users cannot upload directly - only through Artist/Label groups
                    if ($is_own_profile) {
                        echo '<div class="simuze-notice" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 30px; margin: 20px 0;">';
                        echo '<div style="display: flex; align-items: flex-start; gap: 16px;">';
                        echo '<div class="notice-icon" style="width: 20px; height: 20px; border-radius: 4px; background: rgba(168, 213, 186, 0.15); display: flex; align-items: center; justify-content: center; font-size: 12px; color: #a8d5ba; flex-shrink: 0;"><i class="fas fa-info-circle"></i></div>';
                        echo '<div style="flex: 1;">';
                        echo '<h3 style="margin: 0 0 12px 0; color: #fff; font-size: 1.2rem;">Upload Music in Groups</h3>';
                        echo '<p style="margin: 0 0 16px 0; color: rgba(255,255,255,0.7); line-height: 1.6;">Music can only be uploaded through <strong>Artist</strong> or <strong>Label</strong> groups. Join or create an Artist/Label group to start uploading your music.</p>';
                        echo '<div style="display: flex; gap: 12px; flex-wrap: wrap;">';
                        echo '<a href="' . esc_url(add_query_arg('tab', 'groups', $user->get_url())) . '" style="background: #a8d5ba; color: #1a1a1a; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-block;">View My Groups</a>';
                        echo '<a href="' . esc_url(add_query_arg('tab', 'create-group', $user->get_url())) . '" style="background: rgba(255,255,255,0.1); color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-block; border: 1px solid rgba(255,255,255,0.2);">Create Group</a>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="access-denied"><p>Access denied.</p></div>';
                    }
                    break;
                case 'shows':
                    include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-shows.php';
                    break;
                case 'activity':
                    include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-activity.php';
                    break;
                case 'friends':
                    include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-friends.php';
                    break;
                case 'groups':
                    include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-groups.php';
                    break;
                case 'create-group':
                    if ($is_own_profile) {
                        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-create-group.php';
                    } else {
                        echo '<div class="access-denied"><p>Access denied.</p></div>';
                    }
                    break;
                case 'messages':
                    include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-messages.php';
                    break;
                case 'purchases':
                    if ($is_own_profile) {
                        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-purchases.php';
                    } else {
                        echo '<div class="access-denied"><p>Access denied.</p></div>';
                    }
                    break;
                case 'settings':
                    if ($is_own_profile) {
                        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-settings.php';
                    } else {
                        echo '<div class="access-denied"><p>Access denied.</p></div>';
                    }
                    break;
                case 'payment-settings':
                    if ($is_own_profile) {
                        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-payment-settings.php';
                    } else {
                        echo '<div class="access-denied"><p>Access denied.</p></div>';
                    }
                    break;
                default:
                    include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-dashboard.php';
                }
            }
            ?>
        </div>
    </div>
</div>
</section>
