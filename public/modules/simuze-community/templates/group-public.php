<?php
/**
 * Public Group Profile Template
 * Public view of group profile - visible to everyone
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

if (!isset($current_user)) {
    $current_user = class_exists('Simuze_Auth') ? Simuze_Auth::get_instance()->get_current_user() : ($GLOBALS['current_user'] ?? null);
}
$is_owner = $current_user && ($group->creator_id === $current_user->id);
$is_member = $current_user && ($group->is_member($current_user->id) || $is_owner);
$is_admin = $current_user && $group->can_manage($current_user->id);

// Determine group type (artist, label, country, etc.)
$group_type_data = $group->get_group_type();
$group_type_slug = $group_type_data ? $group_type_data['slug'] : null;

// Get public stats
$member_count = $group->get_member_count();
$followers_count = class_exists('Simuze_Follow') ? Simuze_Follow::count_followers($group->id, 'group') : 0;

// Get albums and tracks count — modular tables only (no WP posts)
$albums_count = md_db_var(
    "SELECT COUNT(*) FROM md_music_albums WHERE group_id = ? AND status = 'published' AND deleted_at IS NULL",
    [$group->id]
);

$tracks_count = md_db_var(
    "SELECT COUNT(*) FROM md_music_uploads WHERE group_id = ? AND status = 'published'",
    [$group->id]
);

// Get recent members (public)
$recent_members = md_db_query(
    "SELECT u.*, gm.role
    FROM simuze_group_members gm
    INNER JOIN simuze_users u ON gm.user_id = u.id
    WHERE gm.group_id = ? AND gm.status = 'active'
    ORDER BY gm.joined_at DESC
    LIMIT 6",
    [$group->id]
);

// Auto-derive genres for artist groups based on uploaded music
$derived_genres = array();
if ($group_type_slug === 'artist' && function_exists('simuze_get_artist_genres_from_uploads')) {
    $derived_genres = simuze_get_artist_genres_from_uploads($group->id, 20);
}

// Artist settings — for availability badge + embed code
$group_settings      = is_array($group->settings) ? $group->settings : (is_string($group->settings) ? json_decode($group->settings, true) : []);
$group_settings      = is_array($group_settings) ? $group_settings : [];
$is_available        = !empty($group_settings['available_for_shows']);
$group_type_label    = $group_type_data['name'] ?? ucfirst((string) $group_type_slug);
$embed_url           = md_home_url('/embed/artist/' . $group->id);
?>

<div class="public-profile group-profile">
    
    <!-- Group Info (without header/avatar - those are shown in group.php) -->
    <div class="group-info-public">
        <div class="group-details-public">
            <?php if (!empty($group->description)): ?>
                <p class="group-description"><?php echo esc_html($group->description); ?></p>
            <?php endif; ?>

            <div style="display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem;">
                <?php if (!empty($group_type_label)): ?>
                    <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.06);color:#ddd;border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:.3rem .9rem;font-size:.82rem;font-weight:600;">
                        <i class="fas fa-tag"></i> <?php echo esc_html($group_type_label); ?>
                    </span>
                <?php endif; ?>
                <?php if ($is_available): ?>
                    <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(168,213,186,.12);color:#a8d5ba;border:1px solid rgba(168,213,186,.35);border-radius:20px;padding:.3rem .9rem;font-size:.82rem;font-weight:600;">
                        <i class="fas fa-bolt"></i> Active and looking for shows
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($derived_genres)): ?>
                <div class="group-genres-public">
                    <h3 class="group-genres-title">Genres</h3>
                    <div class="group-genres-tags">
                        <?php foreach ($derived_genres as $genre): ?>
                            <span class="group-genre-tag">
                                <?php echo esc_html($genre); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php
            $public_links = [];
            if (!empty($group_settings['website']) && is_string($group_settings['website'])) {
                $public_links[] = ['label' => 'Website', 'url' => $group_settings['website']];
            }
            if (!empty($group_settings['websites']) && is_array($group_settings['websites'])) {
                foreach ($group_settings['websites'] as $_w) {
                    if (is_string($_w) && trim($_w) !== '') {
                        $public_links[] = ['label' => 'Link', 'url' => $_w];
                    }
                }
            }
            if (!empty($group_settings['links']) && is_array($group_settings['links'])) {
                foreach ($group_settings['links'] as $_lk) {
                    if (is_array($_lk) && !empty($_lk['url'])) {
                        $public_links[] = [
                            'label' => !empty($_lk['label']) ? (string) $_lk['label'] : 'Link',
                            'url'   => (string) $_lk['url'],
                        ];
                    }
                }
            }
            ?>
            <?php if (!empty($public_links)): ?>
                <div class="profile-links" style="display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem;">
                    <?php foreach ($public_links as $_pl): ?>
                        <a href="<?php echo esc_url($_pl['url']); ?>" target="_blank" rel="noopener noreferrer"
                           style="display:inline-flex;align-items:center;gap:6px;padding:.3rem .8rem;border-radius:16px;border:1px solid rgba(255,255,255,.2);text-decoration:none;color:#ddd;font-size:.8rem;">
                            <i class="fas fa-link"></i> <?php echo esc_html($_pl['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Public Stats -->
            <div class="public-stats">
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($member_count); ?></span>
                    <span class="stat-label">Members</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($followers_count); ?></span>
                    <span class="stat-label">Followers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($albums_count); ?></span>
                    <span class="stat-label">Albums</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($tracks_count); ?></span>
                    <span class="stat-label">Tracks</span>
                </div>
            </div>
            
            <!-- Actions -->
            <?php if ($current_user): ?>
                <div class="group-actions">
                    <?php if ($is_member): ?>
                        <?php if ($is_admin): ?>
                            <a href="<?php echo esc_url(add_query_arg('tab', 'dashboard', $group->get_url())); ?>" class="btn btn-primary">
                                View Dashboard
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-outline leave-group" data-group-id="<?php echo esc_attr($group->id); ?>">
                            Leave Group
                        </button>
                    <?php else: ?>
                        <?php
                        $is_following = class_exists('Simuze_Follow') ? Simuze_Follow::is_following($current_user->id, 'group', $group->id) : false;
                        ?>
                        <button class="btn btn-primary join-group" data-group-id="<?php echo esc_attr($group->id); ?>">
                            Join Group
                        </button>
                        <button class="btn btn-outline <?php echo $is_following ? 'unfollow-group' : 'follow-group'; ?>" 
                                data-group-id="<?php echo esc_attr($group->id); ?>"
                                data-action="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                            <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($is_admin && $group_type_slug === 'artist'): ?>
                <details class="embed-code-block" style="margin-top:1.25rem;">
                    <summary style="cursor:pointer;color:#a8d5ba;font-size:.85rem;font-weight:600;list-style:none;display:flex;align-items:center;gap:6px;user-select:none;">
                        <i class="fas fa-code"></i> Embed your shows
                    </summary>
                    <div style="margin-top:.75rem;background:#1a1a1a;border:1px solid #333;border-radius:6px;padding:.75rem 1rem;">
                        <p style="font-size:.8rem;color:#888;margin-bottom:.5rem;">Paste this snippet on any website to show your upcoming shows:</p>
                        <code id="embed-snippet" style="display:block;font-size:.78rem;background:#111;color:#a8d5ba;padding:.6rem .75rem;border-radius:4px;overflow-x:auto;white-space:pre-wrap;word-break:break-all;">&lt;iframe src="<?php echo esc_attr($embed_url); ?>" width="100%" height="400" frameborder="0" style="border:none;"&gt;&lt;/iframe&gt;</code>
                        <button type="button" onclick="(function(){var c=document.getElementById('embed-snippet').textContent;navigator.clipboard&&navigator.clipboard.writeText(c).then(function(){var b=event.target;var o=b.textContent;b.textContent='Copied!';setTimeout(function(){b.textContent=o;},2000);});})()" style="margin-top:.5rem;font-size:.78rem;background:#2a2a2a;color:#ccc;border:1px solid #444;border-radius:4px;padding:.3rem .7rem;cursor:pointer;">Copy</button>
                    </div>
                </details>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Members -->
    <?php if (!empty($recent_members)): ?>
    <div class="profile-section">
        <h2>Members</h2>
        <div class="members-grid-public">
            <?php foreach ($recent_members as $member): 
                $member_user = new Simuze_User($member);
            ?>
                <a href="<?php echo esc_url($member_user->get_url()); ?>" class="member-card-public">
                    <?php
                    // Get avatar URL using SIMEZU fetch method
                    $member_avatar_url = '';
                    $member_simezu_id = $member_user->simezu_id ?? '';
                    if (!empty($member_simezu_id) && class_exists('Simuze_Avatar')) {
                        $avatar = Simuze_Avatar::get_instance();
                        $member_avatar_url = $avatar->get_user_avatar_url($member_simezu_id, 'thumb', array('cache_only' => false));
                        
                        // Fallback: if avatar_url is default/placeholder, go via Modular Simezu auth proxy
                        if (empty($member_avatar_url) || strpos($member_avatar_url, 'default-avatar') !== false || strpos($member_avatar_url, 'data:image') !== false) {
                            $api_url = rtrim(md_home_url('/api/auth'), '/');
                            $member_avatar_url = $api_url . '/users/' . urlencode($member_simezu_id) . '/avatar/image?size=64';
                        }
                    } else {
                        $member_avatar_url = $member_user->get_avatar_url('thumb', array('cache_only' => false));
                    }
                    if (empty($member_avatar_url)) {
                        $avatar_instance = class_exists('Simuze_Avatar') ? Simuze_Avatar::get_instance() : null;
                        $member_avatar_url = $avatar_instance ? $avatar_instance->get_default_avatar_url() : md_asset_url('images/profile_default.jpg');
                    }
                    ?>
                    <img src="<?php echo esc_url($member_avatar_url); ?>" 
                         alt="<?php echo esc_attr($member_user->display_name); ?>" 
                         class="member-avatar user-avatar"
                         <?php if (!empty($member_simezu_id)): ?>data-simezu-user-id="<?php echo esc_attr($member_simezu_id); ?>"<?php endif; ?>
                         onerror="this.src='<?php echo esc_url(theme_uri('/images/profile_default.jpg')); ?>';">
                    <div class="member-info">
                        <h3 class="member-name" title="<?php echo esc_attr($member_user->display_name); ?>"><?php echo esc_html($member_user->display_name); ?></h3>
                        <span class="member-role badge"><?php echo esc_html(ucfirst($member['role'] ?? 'member')); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="view-all-link">
            <a href="<?php echo esc_url(add_query_arg('tab', 'members', $group->get_url())); ?>">View all members →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upcoming Shows organised or participated in by this group -->
    <?php
    $group_shows_organised = class_exists('EventRepository') ? EventRepository::findByOrganiser((int) $group->id, 'group', 6) : [];
    $group_shows_slot      = class_exists('EventRepository') ? EventRepository::findByGroupSlot((int) $group->id, 6) : [];
    // Merge and deduplicate by event id
    $group_shows_all = [];
    foreach (array_merge($group_shows_organised, $group_shows_slot) as $_gs) {
        $group_shows_all[$_gs['id']] = $_gs;
    }
    uasort($group_shows_all, function($a, $b) { return strtotime($b['event_date']) - strtotime($a['event_date']); });
    $group_shows_upcoming = array_filter($group_shows_all, function($ev) {
        return strtotime($ev['event_date']) >= time() && !in_array($ev['status'], ['cancelled','past']);
    });
    $group_shows_past     = array_filter($group_shows_all, function($ev) {
        return strtotime($ev['event_date']) < time() || $ev['status'] === 'past';
    });
    $group_start_show_url = md_home_url('/start-a-show');
    ?>
    <?php if (!empty($group_shows_upcoming) || !empty($group_shows_past)): ?>
    <div class="profile-section group-shows-section">
        <div class="section-header-row">
            <h2>Shows</h2>
            <?php if ($is_admin): ?>
                <a href="<?php echo esc_url($group_start_show_url); ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-bolt"></i> Start a Show
                </a>
            <?php endif; ?>
        </div>
        <?php if (!empty($group_shows_upcoming)): ?>
            <h4 class="shows-subheading">Upcoming</h4>
            <ul class="event-list">
                <?php foreach (array_slice($group_shows_upcoming, 0, 4) as $gs): ?>
                    <?php
                    $gs_date = !empty($gs['event_date']) ? date('D j M Y', strtotime($gs['event_date'])) : '';
                    $gs_url  = md_home_url('/show/' . $gs['slug']);
                    $gs_city = $gs['city_name'] ?? '';
                    $gs_ticket_price = isset($gs['ticket_price']) ? (float) $gs['ticket_price'] : 0.0;
                    $gs_price_label  = $gs_ticket_price > 0 ? 'EUR ' . number_format($gs_ticket_price, 2) : 'Free';
                    $gs_slots = (int) ($gs['open_slots'] ?? 0);
                    ?>
                    <li class="event-list-item">
                        <span class="event-list-date"><?php echo esc_html($gs_date); ?></span>
                        <div class="event-list-info">
                            <a href="<?php echo esc_url($gs_url); ?>" class="event-list-title"><?php echo esc_html($gs['title']); ?></a>
                            <span class="event-list-city"><?php
                                if ($gs_city !== '') echo esc_html($gs_city);
                                if ($gs_slots > 0) echo ($gs_city !== '' ? ' &middot; ' : '') . $gs_slots . ' open slot' . ($gs_slots === 1 ? '' : 's');
                                echo ($gs_city !== '' || $gs_slots > 0 ? ' &middot; ' : '') . esc_html($gs_price_label);
                            ?></span>
                        </div>
                        <a href="<?php echo esc_url($gs_url); ?>" class="event-list-link event-ticket-btn-light">Tickets</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($group_shows_past)): ?>
            <h4 class="shows-subheading">Recent Shows</h4>
            <ul class="event-list event-list-past">
                <?php foreach (array_slice($group_shows_past, 0, 4) as $gs): ?>
                    <?php
                    $gs_date = !empty($gs['event_date']) ? date('j M Y', strtotime($gs['event_date'])) : '';
                    $gs_url  = md_home_url('/show/' . $gs['slug']);
                    ?>
                    <li class="event-list-item event-list-item-past">
                        <span class="event-list-date"><?php echo esc_html($gs_date); ?></span>
                        <a href="<?php echo esc_url($gs_url); ?>" class="event-list-title"><?php echo esc_html($gs['title']); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php elseif ($is_admin): ?>
    <div class="profile-section group-shows-empty">
        <h2>Shows</h2>
        <p>No shows yet.</p>
        <a href="<?php echo esc_url($group_start_show_url); ?>" class="btn btn-primary">
            <i class="fas fa-bolt"></i> Start your first show
        </a>
    </div>
    <?php endif; ?>

</div>

<style>
.group-genres-public {
    margin: 20px 0 16px;
}

.group-genres-title {
    margin: 0 0 8px 0;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255,255,255,0.7);
}

.group-genres-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.group-genre-tag {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    background: rgba(168, 213, 186, 0.16);
    border: 1px solid rgba(168, 213, 186, 0.45);
    color: #a8d5ba;
    font-size: 0.8rem;
    font-weight: 500;
}
</style>
