<?php
/**
 * User Profile Shows Tab
 * Shows created by the user (organiser_type=user) and shows via group membership.
 * $user is the Simuze_User object set by profile.php.
 */

if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
    exit;
}

$uid = (int) ($user->id ?? 0);
if (!$uid) {
    echo '<p>User not found.</p>';
    return;
}

// Shows organised directly by this user
$organised = md_db_query(
    "SELECT e.id, e.title, e.slug, e.event_date, e.status, e.ticket_type,
            c.name AS city_name
     FROM simuze_events e
     LEFT JOIN simuze_cities c ON c.id = e.city_id
     WHERE e.organiser_type = 'user'
       AND e.organiser_id = ?
       AND e.deleted_at IS NULL
     ORDER BY e.event_date DESC
     LIMIT 50",
    [$uid]
) ?: [];

// Shows via groups this user is a member of (as lineup slot)
$via_groups = md_db_query(
    "SELECT DISTINCT e.id, e.title, e.slug, e.event_date, e.status, e.ticket_type,
            c.name AS city_name, g.name AS group_name, g.unique_name AS group_slug
     FROM simuze_events e
     INNER JOIN simuze_event_slots s ON s.event_id = e.id AND s.confirmed = 1
     INNER JOIN simuze_groups g ON g.id = s.group_id
     INNER JOIN simuze_group_members m ON m.group_id = g.id AND m.user_id = ? AND m.status = 'active'
     LEFT JOIN simuze_cities c ON c.id = e.city_id
     WHERE e.deleted_at IS NULL
       AND e.organiser_type != 'user'
     ORDER BY e.event_date DESC
     LIMIT 50",
    [$uid]
) ?: [];

$add_url   = md_home_url('/shows/add' . ($is_own_profile && $uid ? '?user_id=' . (int) $uid : ''));
$ajax_url  = md_url('/api/simezu-ajax.php');
$nonce     = function_exists('md_csrf_token') ? md_csrf_token() : '';
?>

<style>
.show-action-btns { display:flex; gap:.3rem; align-items:center; flex-shrink:0; }
.show-action-btn  {
    display:inline-flex; align-items:center; justify-content:center;
    width:2rem; height:2rem; border-radius:6px;
    border:1px solid var(--color-border,#2f3642);
    background:transparent; color:var(--color-text-muted,#888);
    text-decoration:none; cursor:pointer; font-size:.85rem;
    transition: background .15s, color .15s;
}
.show-action-btn:hover { background:rgba(255,255,255,.07); color:var(--color-text,#fff); }
.show-action-btn.danger:hover { background:rgba(220,38,38,.15); color:#f87171; border-color:#f87171; }
</style>

<div class="profile-shows-tab">
    <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:1.25rem;">
        <h2 style="margin:0;font-size:1.25rem;">Shows</h2>
        <?php if ($is_own_profile): ?>
            <a href="<?php echo esc_url($add_url); ?>" class="button button-sm button-primary">+ Start a show</a>
        <?php endif; ?>
    </div>

    <!-- Organised shows -->
    <?php if (!empty($organised)): ?>
        <h3 style="margin:0 0 .6rem;font-size:.95rem;color:var(--color-text-muted,#aaa);">Organised by you</h3>
        <div style="display:flex;flex-direction:column;gap:.6rem;margin-bottom:1.75rem;">
            <?php foreach ($organised as $ev):
                $ev_date  = !empty($ev['event_date']) ? date('D j M Y', strtotime($ev['event_date'])) : '';
                $ev_url   = md_home_url('/show/' . $ev['slug']);
                $edit_url = md_home_url('/shows/' . $ev['slug'] . '/edit');
            ?>
                <div class="show-card" data-event-id="<?php echo (int)$ev['id']; ?>" style="padding:.75rem 1rem;border:1px solid var(--color-border,#2f3642);border-radius:8px;background:rgba(255,255,255,0.02);display:flex;align-items:center;gap:.75rem;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <a href="<?php echo esc_url($ev_url); ?>" style="text-decoration:none;color:inherit;"><?php echo esc_html($ev['title']); ?></a>
                        </div>
                        <div style="font-size:.83rem;color:var(--color-text-muted,#888);margin-top:.1rem;">
                            <?php echo esc_html($ev_date); ?>
                            <?php if (!empty($ev['city_name'])): ?>&middot; <?php echo esc_html($ev['city_name']); ?><?php endif; ?>
                        </div>
                    </div>
                    <div class="show-action-btns">
                        <a href="<?php echo esc_url($ev_url); ?>" class="show-action-btn" title="View"><i class="fas fa-eye"></i></a>
                        <?php if ($is_own_profile): ?>
                            <a href="<?php echo esc_url($edit_url); ?>" class="show-action-btn" title="Edit"><i class="fas fa-pen"></i></a>
                            <button type="button" class="show-action-btn danger show-delete-btn" title="Delete" data-event-id="<?php echo (int)$ev['id']; ?>"><i class="fas fa-trash-alt"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Shows via groups -->
    <?php if (!empty($via_groups)): ?>
        <h3 style="margin:0 0 .6rem;font-size:.95rem;color:var(--color-text-muted,#aaa);">Confirmed in lineup</h3>
        <div style="display:flex;flex-direction:column;gap:.6rem;margin-bottom:1.75rem;">
            <?php foreach ($via_groups as $ev):
                $ev_date  = !empty($ev['event_date']) ? date('D j M Y', strtotime($ev['event_date'])) : '';
                $ev_url   = md_home_url('/show/' . $ev['slug']);
                $grp_url  = !empty($ev['group_slug']) ? md_home_url('/group/' . $ev['group_slug']) : null;
            ?>
                <div class="show-card" data-event-id="<?php echo (int)$ev['id']; ?>" style="padding:.75rem 1rem;border:1px solid var(--color-border,#2f3642);border-radius:8px;background:rgba(255,255,255,0.02);display:flex;align-items:center;gap:.75rem;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <a href="<?php echo esc_url($ev_url); ?>" style="text-decoration:none;color:inherit;"><?php echo esc_html($ev['title']); ?></a>
                        </div>
                        <div style="font-size:.83rem;color:var(--color-text-muted,#888);margin-top:.1rem;">
                            <?php echo esc_html($ev_date); ?>
                            <?php if (!empty($ev['city_name'])): ?>&middot; <?php echo esc_html($ev['city_name']); ?><?php endif; ?>
                            <?php if (!empty($ev['group_name'])): ?>
                                &middot; via <?php if ($grp_url): ?><a href="<?php echo esc_url($grp_url); ?>" style="color:inherit;"><?php echo esc_html($ev['group_name']); ?></a><?php else: echo esc_html($ev['group_name']); ?><?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="show-action-btns">
                        <a href="<?php echo esc_url($ev_url); ?>" class="show-action-btn" title="View"><i class="fas fa-eye"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($organised) && empty($via_groups)): ?>
        <div style="color:var(--color-text-muted,#888);padding:1rem 0;">
            No shows yet.
            <?php if ($is_own_profile): ?>
                <a href="<?php echo esc_url($add_url); ?>">Add one.</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($is_own_profile): ?>
<script>
(function() {
    var ajaxUrl   = <?php echo json_encode($ajax_url); ?>;
    var csrfToken = <?php echo json_encode($nonce); ?>;

    document.querySelectorAll('.show-delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this show? This cannot be undone.')) return;
            var eventId = btn.getAttribute('data-event-id');
            btn.disabled = true;

            var fd = new FormData();
            fd.append('action', 'simuze_delete_show');
            fd.append('nonce', csrfToken);
            fd.append('event_id', eventId);

            fetch(ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.success) {
                        var card = btn.closest('.show-card');
                        if (card) card.remove();
                    } else {
                        alert((data && data.data) ? data.data : 'Could not delete show.');
                        btn.disabled = false;
                    }
                })
                .catch(function() {
                    alert('Could not complete action.');
                    btn.disabled = false;
                });
        });
    });
})();
</script>
<?php endif; ?>
