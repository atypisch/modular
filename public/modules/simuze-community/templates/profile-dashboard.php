<?php
/**
 * User Profile Dashboard Template
 * Playful widget-based layout - OWNER ONLY
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

// Ensure $user is available
if (!isset($user) || !$user) {
    echo '<div class="simuze-error"><p>User not found.</p></div>';
    return;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_own_profile = $current_user && $current_user->id === $user->id;

if (!$is_own_profile && isset($GLOBALS['current_user']) && is_object($GLOBALS['current_user'])) {
    $jwt_user     = $GLOBALS['current_user'];
    $jwt_simezu   = $jwt_user->simezu_id ?? ($jwt_user->id ?? null);
    $jwt_email    = $jwt_user->email ?? null;
    if (!empty($jwt_simezu) && !empty($user->simezu_id) && $jwt_simezu === $user->simezu_id) {
        $is_own_profile = true;
    } elseif (!empty($jwt_email) && !empty($user->email) && $jwt_email === $user->email) {
        $is_own_profile = true;
    }
}

// Dashboard is only visible to owner
if (!$is_own_profile) {
    // Redirect to public profile view
    $redirect_to = rtrim($user->get_url(), '/') . '?view=profile';
    header('Location: ' . $redirect_to);
    exit;
}

// Check if user is site admin
$is_superuser = $is_own_profile && UserRepository::currentIsAdmin();

// Get user stats
$stats = array(
    'groups' => Simuze_Group::count_user_groups($user->id),
    'friends' => class_exists('Simuze_Friend') ? Simuze_Friend::count_friends($user->id) : 0,
    'followers' => class_exists('Simuze_Follow') ? Simuze_Follow::count_followers($user->id) : 0,
    'following' => class_exists('Simuze_Follow') ? Simuze_Follow::count_following($user->id) : 0,
    'playlists' => function_exists('simuze_get_user_playlists') ? count(simuze_get_user_playlists($user->id)) : 0,
    'purchases' => (int) md_db_var("SELECT COUNT(*) FROM simuze_download_grants WHERE user_id = ?", [$user->id]),
    'downloads' => (int) md_db_var("SELECT COUNT(*) FROM simuze_download_grants WHERE user_id = ?", [$user->id]),
    'unread_messages' => class_exists('Simuze_Message') ? Simuze_Message::count_unread($user->id) : 0,
    'pending_requests' => class_exists('Simuze_Friend') ? Simuze_Friend::count_pending_requests($user->id) : 0,
);

// Get music statistics for artists — modular tables only (no WP posts)
$music_stats = array(
    'albums' => 0,
    'tracks' => 0,
    'total_plays' => 0,
    'recent_uploads' => 0,
);

// Count albums where user is artist
$music_stats['albums'] = (int) md_db_var(
    "SELECT COUNT(*) FROM md_music_albums WHERE artist_id = ? AND status = 'published' AND deleted_at IS NULL",
    [$user->id]
);

// Count tracks where user is artist
$music_stats['tracks'] = (int) md_db_var(
    "SELECT COUNT(*) FROM md_music_uploads WHERE artist_id = ? AND status = 'published'",
    [$user->id]
);

// Count recent uploads (last 30 days)
$music_stats['recent_uploads'] = (int) md_db_var(
    "SELECT COUNT(*) FROM (
        SELECT id FROM md_music_albums WHERE artist_id = ? AND status = 'published' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND deleted_at IS NULL
        UNION ALL
        SELECT id FROM md_music_uploads WHERE artist_id = ? AND status = 'published' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ) recent",
    [$user->id, $user->id]
);

// Get recent activity
$recent_activity = class_exists('Simuze_Activity') ? Simuze_Activity::get_user_activity($user->id, 8) : array();

// Get user's groups
$user_groups = Simuze_Group::get_user_groups($user->id, 6);

// Get Artist Groups (groups that can upload music)
$artist_groups = array();
if (!empty($user_groups)) {
    foreach ($user_groups as $group_data) {
        $group = is_array($group_data) ? Simuze_Group::get($group_data['id']) : $group_data;
        if ($group && $group->can_upload_music()) {
            $artist_groups[] = $group;
        }
    }
}

// Superuser platform stats with chart data
$platform_stats = array();
$chart_data = array();
if ($is_superuser) {
    $platform_stats = array(
        'total_users'    => (int) md_db_var("SELECT COUNT(*) FROM simuze_users WHERE deleted_at IS NULL"),
        'total_groups'   => (int) md_db_var("SELECT COUNT(*) FROM simuze_groups WHERE deleted_at IS NULL"),
        'total_orders'   => (int) md_db_var("SELECT COUNT(*) FROM simuze_download_grants"),
        'total_revenue'  => 0,
        'active_sessions'=> (int) md_db_var("SELECT COUNT(*) FROM simuze_users WHERE updated_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)"),
    );

    $chart_data['users'] = array();
    for ($i = 6; $i >= 0; $i--) {
        $date  = date('Y-m-d', strtotime("-$i days"));
        $count = md_db_var("SELECT COUNT(*) FROM simuze_users WHERE DATE(created_at) = ? AND deleted_at IS NULL", [$date]);
        $chart_data['users'][] = array('date' => $date, 'count' => (int) $count);
    }
    $chart_data['revenue'] = array();
    for ($i = 6; $i >= 0; $i--) {
        $chart_data['revenue'][] = array('date' => date('Y-m-d', strtotime("-$i days")), 'amount' => 0);
    }
    $chart_data['orders'] = array();
    for ($i = 6; $i >= 0; $i--) {
        $date  = date('Y-m-d', strtotime("-$i days"));
        $count = md_db_var("SELECT COUNT(*) FROM simuze_download_grants WHERE DATE(created_at) = ?", [$date]);
        $chart_data['orders'][] = array('date' => $date, 'count' => (int) $count);
    }
}
?>

<div class="playful-dashboard user-dashboard">
    
    <!-- Welcome Header -->
    <div class="dashboard-header">
        <h1>Welcome back, <?php echo esc_html($user->display_name); ?></h1>
        <p class="dashboard-subtitle">Here's what's happening with your account</p>
    </div>
    
    <?php if ($is_superuser): ?>
    <!-- Platform Metrics Section (Super Admin Only) -->
    <div class="platform-metrics-section">
        <div class="platform-metrics-header">
            <h2>Platform Metrics</h2>
            <a href="<?php echo htmlspecialchars(md_url('/admin/module-page.php?module=simuze-community&page=index'), ENT_QUOTES); ?>" class="platform-metrics-link">View Full Analytics →</a>
        </div>
        
        <!-- Key Metrics Row -->
        <div class="platform-metrics-grid">
            <div class="platform-metric-card">
                <div class="platform-metric-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="platform-metric-content">
                    <div class="platform-metric-value"><?php echo number_format($platform_stats['total_users']); ?></div>
                    <div class="platform-metric-label">Total Users</div>
                </div>
            </div>
            
            <div class="platform-metric-card">
                <div class="platform-metric-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="platform-metric-content">
                    <div class="platform-metric-value"><?php echo number_format($platform_stats['total_groups']); ?></div>
                    <div class="platform-metric-label">Total Groups</div>
                </div>
            </div>
            
            <div class="platform-metric-card">
                <div class="platform-metric-icon">
                    <i class="fas fa-circle"></i>
                </div>
                <div class="platform-metric-content">
                    <div class="platform-metric-value"><?php echo $platform_stats['active_sessions']; ?></div>
                    <div class="platform-metric-label">Active Now</div>
                </div>
            </div>
            
            <div class="platform-metric-card">
                <div class="platform-metric-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="platform-metric-content">
                    <div class="platform-metric-value"><?php echo number_format($platform_stats['total_orders']); ?></div>
                    <div class="platform-metric-label">Total Orders</div>
                </div>
            </div>
            
            <div class="platform-metric-card highlight">
                <div class="platform-metric-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="platform-metric-content">
                    <div class="platform-metric-value">€<?php echo number_format($platform_stats['total_revenue'], 2); ?></div>
                    <div class="platform-metric-label">Total Revenue</div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="platform-charts-grid">
            <div class="platform-chart-card">
                <div class="platform-chart-header">
                    <h3>New Users (Last 7 Days)</h3>
                </div>
                <div class="platform-chart-container">
                    <canvas id="users-chart"></canvas>
                </div>
            </div>
            
            <div class="platform-chart-card">
                <div class="platform-chart-header">
                    <h3>Revenue (Last 7 Days)</h3>
                </div>
                <div class="platform-chart-container">
                    <canvas id="revenue-chart"></canvas>
                </div>
            </div>
            
            <div class="platform-chart-card">
                <div class="platform-chart-header">
                    <h3>Orders (Last 7 Days)</h3>
                </div>
                <div class="platform-chart-container">
                    <canvas id="orders-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Widget Grid -->
    <div class="widget-grid">
        
        <!-- Row 1: Unread Messages (1 col), Friends (1 col), Groups (2 cols) -->
        <div class="widget widget-stat widget-col-3" data-link="<?php echo esc_url(add_query_arg('tab', 'messages', $user->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($stats['unread_messages']); ?></div>
                <div class="widget-label">Unread Messages</div>
            </div>
            <?php if ($stats['unread_messages'] > 0): ?>
                <div class="widget-badge"><?php echo $stats['unread_messages']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="widget widget-stat widget-col-3" data-link="<?php echo esc_url(add_query_arg('tab', 'friends', $user->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($stats['friends']); ?></div>
                <div class="widget-label">Friends</div>
            </div>
            <?php if ($stats['pending_requests'] > 0): ?>
                <div class="widget-badge"><?php echo $stats['pending_requests']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="widget widget-groups-list widget-col-6" data-link="<?php echo esc_url(add_query_arg('tab', 'groups', $user->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($stats['groups']); ?></div>
                <div class="widget-label">Groups</div>
                <?php if (!empty($user_groups)): ?>
                    <div class="groups-list">
                        <?php foreach (array_slice($user_groups, 0, 3) as $group_data): 
                            $group = is_array($group_data) ? Simuze_Group::get($group_data['id']) : $group_data;
                            if (!$group) continue;
                            $is_group_admin = $group->can_manage($user->id);
                            $has_new_messages = $is_group_admin && class_exists('Simuze_Message') ? Simuze_Message::count_unread_for_group($group->id) > 0 : false;
                        ?>
                            <a href="<?php echo esc_url($group->get_url()); ?>" class="group-item <?php echo $has_new_messages ? 'has-new' : ''; ?>">
                                <span class="group-name"><?php echo esc_html($group->name); ?></span>
                                <?php if ($has_new_messages): ?>
                                    <span class="group-badge">New</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Row 2: Playlists, Followers, Following (3 equal columns) -->
        <div class="widget widget-stat widget-col-4" data-link="<?php echo esc_url(add_query_arg('tab', 'playlists', $user->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($stats['playlists']); ?></div>
                <div class="widget-label">Playlists</div>
            </div>
        </div>
        
        <div class="widget widget-stat widget-col-4" data-link="<?php echo esc_url(add_query_arg('tab', 'activity', $user->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($stats['followers']); ?></div>
                <div class="widget-label">Followers</div>
            </div>
        </div>
        
        <div class="widget widget-stat widget-col-4" data-link="<?php echo esc_url(add_query_arg('tab', 'activity', $user->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($stats['following']); ?></div>
                <div class="widget-label">Following</div>
            </div>
        </div>
        
        <!-- Row 3: Downloads, Purchases, Your Music (if has artist groups) -->
        <div class="widget widget-stat widget-col-3" data-link="<?php echo esc_url(add_query_arg('tab', 'purchases', $user->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-download"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($stats['downloads']); ?></div>
                <div class="widget-label">Downloads</div>
            </div>
        </div>
        
        <div class="widget widget-stat widget-col-3" data-link="<?php echo esc_url(add_query_arg('tab', 'purchases', $user->get_url())); ?>">
            <div class="widget-icon">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="widget-content">
                <div class="widget-value"><?php echo number_format($stats['purchases']); ?></div>
                <div class="widget-label">Purchases</div>
            </div>
        </div>
        
        <?php if (!empty($artist_groups)): ?>
        <div class="widget widget-music-links widget-col-6">
            <div class="widget-icon">
                <i class="fas fa-music"></i>
            </div>
            <div class="widget-content">
                <div class="widget-label">Your Music</div>
                <div class="music-groups-list">
                    <?php foreach ($artist_groups as $group): ?>
                        <a href="<?php echo esc_url(trailingslashit($group->get_url()) . 'music'); ?>" class="music-group-link">
                            <?php echo esc_html($group->name); ?> music
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Row 4: Activity Feed (Chapter, not widget) -->
        <?php if (!empty($recent_activity)): ?>
        <div class="dashboard-chapter">
            <div class="chapter-header">
                <h3>Recent Activity</h3>
                <a href="<?php echo esc_url(add_query_arg('tab', 'activity', $user->get_url())); ?>" class="chapter-link">View all →</a>
            </div>
            <div class="activity-feed">
                <?php foreach (array_slice($recent_activity, 0, 6) as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-content">
                            <?php echo wp_kses_post($activity['content'] ?? Simuze_Activity::format_activity($activity)); ?>
                        </div>
                        <div class="activity-time"><?php echo esc_html(human_time_diff(strtotime($activity['created_at']))); ?> ago</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Row 5: Groups Grid (Chapter, not widget) -->
        <?php if (!empty($user_groups)): ?>
        <div class="dashboard-chapter">
            <div class="chapter-header">
                <h3>Your Groups</h3>
                <a href="<?php echo esc_url(add_query_arg('tab', 'groups', $user->get_url())); ?>" class="chapter-link">View all →</a>
            </div>
            <div class="groups-grid">
                <?php foreach ($user_groups as $group_data): 
                    $group = is_array($group_data) ? Simuze_Group::get($group_data['id']) : $group_data;
                    if (!$group) continue;
                    $role = isset($group_data['member_role']) ? $group_data['member_role'] : (isset($group_data['role']) ? $group_data['role'] : 'member');
                ?>
                    <div class="group-card">
                        <a href="<?php echo esc_url($group->get_url()); ?>" title="<?php echo esc_attr($group->name); ?>">
                            <img src="<?php echo esc_url($group->get_avatar_url('thumb')); ?>" 
                                 alt="<?php echo esc_attr($group->name); ?>" 
                                 class="group-avatar"
                                 onerror="this.src='<?php echo htmlspecialchars(md_asset_url('images/default-group.svg'), ENT_QUOTES); ?>';">
                            <div class="group-info">
                                <h3 class="group-name" title="<?php echo esc_attr($group->name); ?>"><?php echo esc_html($group->name); ?></h3>
                                <span class="group-members"><?php echo esc_html($group->get_member_count()); ?> members</span>
                                <span class="group-role badge"><?php echo esc_html(ucfirst($role)); ?></span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions (Chapter, not widget) -->
        <div class="dashboard-chapter">
            <div class="chapter-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="actions-grid">
                <a href="<?php echo esc_url(trailingslashit($user->get_url()) . 'playlists/'); ?>" class="action-card">
                    <i class="fas fa-plus"></i>
                    <span>Create Playlist</span>
                </a>
                <a href="<?php echo esc_url(trailingslashit($user->get_url()) . 'create-group/'); ?>" class="action-card">
                    <i class="fas fa-users"></i>
                    <span>Create Group</span>
                </a>
                <a href="<?php echo esc_url(trailingslashit($user->get_url()) . 'settings/'); ?>" class="action-card">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>
        
    </div>
</div>

<?php if ($is_superuser && !empty($chart_data)): ?>
<script>
jQuery(document).ready(function($) {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js not loaded');
        return;
    }
    
    // Chart data from PHP
    const chartData = <?php echo json_encode($chart_data); ?>;
    
    // Users Chart
    const usersCtx = document.getElementById('users-chart');
    if (usersCtx) {
        new Chart(usersCtx, {
            type: 'line',
            data: {
                labels: chartData.users.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'New Users',
                    data: chartData.users.map(d => d.count),
                    borderColor: '#a8d5ba',
                    backgroundColor: 'rgba(168, 213, 186, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'rgba(255,255,255,0.6)'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'rgba(255,255,255,0.6)'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    }
                }
            }
        });
    }
    
    // Revenue Chart
    const revenueCtx = document.getElementById('revenue-chart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: chartData.revenue.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Revenue (€)',
                    data: chartData.revenue.map(d => d.amount),
                    backgroundColor: 'rgba(168, 213, 186, 0.3)',
                    borderColor: '#a8d5ba',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'rgba(255,255,255,0.6)',
                            callback: function(value) {
                                return '€' + value.toFixed(2);
                            }
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'rgba(255,255,255,0.6)'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    }
                }
            }
        });
    }
    
    // Orders Chart
    const ordersCtx = document.getElementById('orders-chart');
    if (ordersCtx) {
        new Chart(ordersCtx, {
            type: 'line',
            data: {
                labels: chartData.orders.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Orders',
                    data: chartData.orders.map(d => d.count),
                    borderColor: '#a8d5ba',
                    backgroundColor: 'rgba(168, 213, 186, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'rgba(255,255,255,0.6)'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'rgba(255,255,255,0.6)'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>
