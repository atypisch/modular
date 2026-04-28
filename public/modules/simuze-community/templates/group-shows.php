<?php
/**
 * Band Group Shows - pending show-slot invitations + organiser shows
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

// $group and $current_user come from group.php include scope.
$current_user     = $current_user ?? null;
$can_manage_group = $current_user && method_exists($group, 'can_manage') ? (bool) $group->can_manage($current_user->id) : false;

if (!$can_manage_group) {
    echo '<div class="access-denied"><p>Access denied.</p></div>';
    return;
}

$ajax_url     = md_url('/api/simezu-ajax.php');
$nonce        = md_csrf_token();
$add_show_url = md_home_url('/shows/add?group_id=' . (int) $group->id);

$pending = md_db_query(
    "SELECT
        i.id AS invitation_id,
        i.slot_id,
        e.title AS event_title,
        e.slug AS event_slug,
        e.event_date,
        c.name AS city_name,
        e.open_slots,
        e.status AS event_status
     FROM simuze_event_slot_invitations i
     INNER JOIN simuze_events e ON e.id = i.event_id
     LEFT JOIN simuze_cities c ON c.id = e.city_id
     WHERE i.band_group_id = ?
       AND i.status = 'pending'
       AND e.deleted_at IS NULL
     ORDER BY e.event_date ASC, i.created_at DESC",
    [(int) $group->id]
);

// Confirmed upcoming shows this group has a slot in
$confirmed_shows = class_exists('EventRepository') ? EventRepository::findByGroupSlot((int) $group->id, 20) : [];
// Organiser shows (shows this group created)
$organiser_shows = class_exists('EventRepository') ? EventRepository::findByOrganiser((int) $group->id, 'group', 20) : [];

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

<div class="group-shows-page">
    <div class="page-header-row" style="display:flex;align-items:baseline;gap:1rem;margin-bottom:1.25rem;">
        <h2 style="margin:0;">Shows</h2>
        <a href="<?php echo esc_url($add_show_url); ?>" class="button button-sm button-primary" style="margin-left:auto;">+ Start a show</a>
    </div>

    <!-- ===== CONFIRMED SHOWS ===== -->
    <?php
    // Merge organiser shows + slot shows, deduplicate by id, sort by event_date
    $all_confirmed = [];
    foreach (array_merge($organiser_shows, $confirmed_shows) as $ev) {
        $all_confirmed[$ev['id']] = $ev;
    }
    usort($all_confirmed, function($a, $b) {
        return strcmp($a['event_date'] ?? '', $b['event_date'] ?? '');
    });
    ?>

    <?php if (!empty($all_confirmed)): ?>
        <h3 style="margin:0 0 .6rem 0; font-size:1rem;">Upcoming shows</h3>
        <div class="group-shows-list" style="display:flex;flex-direction:column;gap:.6rem; margin-bottom:1.5rem;">
            <?php foreach ($all_confirmed as $ev):
                $ev_date_fmt  = !empty($ev['event_date']) ? date('D j M Y', strtotime((string) $ev['event_date'])) : '';
                $ev_url       = !empty($ev['slug']) ? md_home_url('/show/' . $ev['slug']) : md_home_url('/shows');
                $edit_url     = !empty($ev['slug']) ? md_home_url('/shows/' . $ev['slug'] . '/edit') : null;
                $is_organiser = ((string)($ev['organiser_type'] ?? '') === 'group' && (int)($ev['organiser_id'] ?? 0) === (int)$group->id);
            ?>
                <div class="show-card" data-event-id="<?php echo (int)$ev['id']; ?>" style="padding:.8rem 1rem;border:1px solid var(--color-border,#2f3642);border-radius:8px;background:rgba(255,255,255,0.02); display:flex; align-items:center; gap:.75rem;">
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <a href="<?php echo esc_url($ev_url); ?>" style="text-decoration:none; color:inherit;"><?php echo esc_html((string) $ev['title']); ?></a>
                        </div>
                        <div style="font-size:.85rem; color:var(--color-text-muted,#888); margin-top:.15rem;">
                            <?php echo esc_html($ev_date_fmt); ?>
                            <?php if (!empty($ev['city_name'])): ?>&middot; <?php echo esc_html((string)$ev['city_name']); ?><?php endif; ?>
                        </div>
                    </div>
                    <div class="show-action-btns">
                        <?php if ($is_organiser): ?>
                            <span style="font-size:.72rem; padding:.15rem .45rem; border-radius:20px; background:rgba(2,132,199,.12); color:#0284c7; margin-right:.25rem;">Organiser</span>
                        <?php else: ?>
                            <span style="font-size:.72rem; padding:.15rem .45rem; border-radius:20px; background:rgba(5,150,105,.12); color:#059669; margin-right:.25rem;">Confirmed</span>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($ev_url); ?>" class="show-action-btn" title="View"><i class="fas fa-eye"></i></a>
                        <?php if ($is_organiser && $edit_url): ?>
                            <a href="<?php echo esc_url($edit_url); ?>" class="show-action-btn" title="Edit"><i class="fas fa-pen"></i></a>
                            <button type="button" class="show-action-btn danger show-delete-btn" title="Delete" data-event-id="<?php echo (int)$ev['id']; ?>"><i class="fas fa-trash-alt"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ===== PENDING INVITATIONS ===== -->
    <?php if (!empty($pending)): ?>
        <h3 style="margin:0 0 .6rem 0; font-size:1rem;">Pending invitations</h3>
        <div class="group-shows-list" style="display:flex;flex-direction:column;gap:.8rem;">
            <?php foreach ($pending as $row):
                $event_date_fmt = !empty($row['event_date']) ? date('D j M Y', strtotime((string) $row['event_date'])) : '';
                $show_url = !empty($row['event_slug']) ? md_home_url('/show/' . $row['event_slug']) : md_home_url('/shows');
            ?>
                <div class="group-show-invite-card" style="padding:1rem;border:1px solid var(--color-border,#2f3642);border-radius:10px;background:rgba(255,255,255,0.03);">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;margin-bottom:.35rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <a href="<?php echo esc_url($show_url); ?>" style="text-decoration:none;color:inherit;">
                                    <?php echo esc_html((string) $row['event_title']); ?>
                                </a>
                            </div>
                            <div style="font-size:.9rem;color:var(--color-text-muted,#888);">
                                <?php echo esc_html($event_date_fmt); ?>
                                <?php if (!empty($row['city_name'])): ?>
                                    &middot; <?php echo esc_html((string) $row['city_name']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display:flex;gap:.6rem;align-items:center;flex-shrink:0;">
                            <button type="button"
                                    class="button button-sm button-primary acceptShowInviteBtn"
                                    data-invitation-id="<?php echo (int) $row['invitation_id']; ?>">
                                Accept
                            </button>
                            <button type="button"
                                    class="button button-sm button-outline declineShowInviteBtn"
                                    data-invitation-id="<?php echo (int) $row['invitation_id']; ?>">
                                Decline
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (empty($all_confirmed)): ?>
        <div style="color:var(--color-text-muted,#888);">No shows yet. <a href="<?php echo esc_url($add_show_url); ?>">Add one.</a></div>
    <?php endif; ?>
</div>

<script>
(function() {
    var ajaxUrl   = <?php echo json_encode($ajax_url); ?>;
    var csrfToken = <?php echo json_encode($nonce); ?>;

    // Invitation accept/decline
    function sendAction(action, invitationId) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('nonce', csrfToken);
        fd.append('invitation_id', invitationId);
        return fetch(ajaxUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); });
    }

    function bindButtons(selector, actionName) {
        document.querySelectorAll(selector).forEach(function(btn) {
            btn.addEventListener('click', function() {
                var invitationId = btn.getAttribute('data-invitation-id');
                if (!invitationId) return;
                btn.disabled = true;
                btn.textContent = actionName === 'simuze_accept_show_slot_invitation' ? 'Accepting...' : 'Declining...';
                sendAction(actionName, invitationId)
                    .then(function(data) {
                        if (!data || !data.success) {
                            alert((data && data.data) ? data.data : 'Action failed');
                            btn.disabled = false;
                            btn.textContent = actionName === 'simuze_accept_show_slot_invitation' ? 'Accept' : 'Decline';
                            return;
                        }
                        window.location.reload();
                    })
                    .catch(function() {
                        btn.disabled = false;
                        btn.textContent = actionName === 'simuze_accept_show_slot_invitation' ? 'Accept' : 'Decline';
                        alert('Could not complete action');
                    });
            });
        });
    }

    bindButtons('.acceptShowInviteBtn', 'simuze_accept_show_slot_invitation');
    bindButtons('.declineShowInviteBtn', 'simuze_decline_show_slot_invitation');

    // Show delete (organiser-only cards)
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
