<?php
/**
 * Simuze Community — Shows admin
 * CRUD for simuze_events. Data lives exclusively here; no posts table used.
 * URL: admin/module-page.php?module=simuze-community&page=shows
 */
$db = $GLOBALS['db'] ?? null;
if (!$db) { echo '<p>Database unavailable.</p>'; return; }

$baseUrl    = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=shows';
$publicBase = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
$csrf_token = function_exists('md_csrf_token') ? md_csrf_token() : '';

$action  = $_GET['action'] ?? '';
$id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ------------------------------------------------------------------
// POST handlers (edit / add)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'delete' && !empty($_POST['id'])) {
        $pid = (int) $_POST['id'];
        $db->prepare("UPDATE simuze_events SET deleted_at = NOW(), status = 'cancelled' WHERE id = ?")->execute([$pid]);
        header('Location: ' . $baseUrl . '&deleted=1');
        exit;
    }

    if (in_array($postAction, ['add', 'edit'], true)) {
        $title           = trim($_POST['title'] ?? '');
        $event_date      = trim($_POST['event_date'] ?? '');
        $status_val      = in_array($_POST['status'] ?? '', ['draft','open','lineup_set','confirmed','past','cancelled'], true) ? $_POST['status'] : 'draft';
        $organiser_type  = in_array($_POST['organiser_type'] ?? '', ['user','group'], true) ? $_POST['organiser_type'] : 'user';
        $organiser_id    = (int) ($_POST['organiser_id'] ?? 0);
        $city_id         = !empty($_POST['city_id']) ? (int) $_POST['city_id'] : null;
        $venue_label     = trim($_POST['venue_label'] ?? '') ?: null;
        $description     = trim($_POST['description'] ?? '') ?: null;
        $ticket_type     = in_array($_POST['ticket_type'] ?? '', ['none','simuze','external'], true) ? $_POST['ticket_type'] : 'none';
        $ticket_price    = isset($_POST['ticket_price']) ? round((float) $_POST['ticket_price'], 2) : 0.0;
        $ticket_capacity = !empty($_POST['ticket_capacity']) ? (int) $_POST['ticket_capacity'] : null;
        $open_slots      = max(0, (int) ($_POST['open_slots'] ?? 0));
        $pid             = (int) ($_POST['id'] ?? 0);

        $formError = '';
        if ($title === '' || $event_date === '') {
            $formError = 'Title and date are required.';
        } else {
            $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($title));
            $slug = preg_replace('/-+/', '-', trim($slug, '-'));

            if ($postAction === 'add') {
                $n = 0; $baseSlug = $slug;
                while (true) {
                    $chk = $db->prepare("SELECT id FROM simuze_events WHERE slug = ? AND deleted_at IS NULL");
                    $chk->execute([$slug]);
                    if (!$chk->fetchColumn()) break;
                    $slug = $baseSlug . '-' . (++$n);
                }
                $ins = $db->prepare(
                    "INSERT INTO simuze_events
                     (title, slug, event_date, status, organiser_type, organiser_id, city_id, venue_label,
                      description, ticket_type, ticket_price, ticket_capacity, open_slots, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
                );
                $ins->execute([$title, $slug, $event_date, $status_val, $organiser_type, $organiser_id,
                               $city_id, $venue_label, $description, $ticket_type, $ticket_price,
                               $ticket_capacity, $open_slots]);
                $newId = $db->lastInsertId();
                header('Location: ' . $baseUrl . '&action=edit&id=' . $newId . '&saved=1');
                exit;
            } else {
                $upd = $db->prepare(
                    "UPDATE simuze_events SET
                     title=?, event_date=?, status=?, organiser_type=?, organiser_id=?,
                     city_id=?, venue_label=?, description=?, ticket_type=?, ticket_price=?,
                     ticket_capacity=?, open_slots=?, updated_at=NOW()
                     WHERE id=? AND deleted_at IS NULL"
                );
                $upd->execute([$title, $event_date, $status_val, $organiser_type, $organiser_id,
                               $city_id, $venue_label, $description, $ticket_type, $ticket_price,
                               $ticket_capacity, $open_slots, $pid]);
                header('Location: ' . $baseUrl . '&action=edit&id=' . $pid . '&saved=1');
                exit;
            }
        }
    }
}

// ------------------------------------------------------------------
// GET delete (legacy — kept for backwards compat)
// ------------------------------------------------------------------
if ($action === 'delete' && $id > 0) {
    $csrf = $_GET['_csrf'] ?? '';
    if (function_exists('md_verify_csrf') && md_verify_csrf($csrf)) {
        $db->prepare("UPDATE simuze_events SET deleted_at = NOW(), status = 'cancelled' WHERE id = ?")->execute([$id]);
        header('Location: ' . $baseUrl . '&deleted=1');
        exit;
    }
}

// ------------------------------------------------------------------
// Load cities for dropdowns
// ------------------------------------------------------------------
$cities = [];
try {
    $stmt = $db->query("SELECT id, name FROM simuze_cities WHERE deleted_at IS NULL ORDER BY name ASC LIMIT 500");
    $cities = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

// ------------------------------------------------------------------
// Edit / New form
// ------------------------------------------------------------------
if ($action === 'edit' || $action === 'new') {
    $ev = [];
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM simuze_events WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $ev = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        if (empty($ev)) {
            header('Location: ' . $baseUrl . '&notfound=1');
            exit;
        }
    }
    $isEdit     = !empty($ev);
    $postAction = $isEdit ? 'edit' : 'add';
    $formError  = $formError ?? '';
    ?>
    <div class="container">
        <div class="page-header">
            <div class="flex justify-between items-center">
                <h1><?php echo $isEdit ? 'Edit Show' : 'Add Show'; ?></h1>
                <a href="<?php echo htmlspecialchars($baseUrl); ?>" class="btn btn-outline">← All Shows</a>
            </div>
        </div>

        <?php if (!empty($_GET['saved'])): ?>
            <div class="alert alert-success">Show saved.</div>
        <?php endif; ?>
        <?php if (!empty($formError)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($formError); ?></div>
        <?php endif; ?>

        <div class="card" style="padding:1.5rem;">
            <form method="POST" action="<?php echo htmlspecialchars($baseUrl); ?>">
                <input type="hidden" name="action" value="<?php echo $postAction; ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$ev['id']; ?>">
                <?php endif; ?>

                <div class="form-row" style="display:grid;grid-template-columns:2fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" required maxlength="255"
                               value="<?php echo htmlspecialchars($ev['title'] ?? $_POST['title'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <?php foreach (['draft','open','lineup_set','confirmed','past','cancelled'] as $s): ?>
                                <option value="<?php echo $s; ?>"
                                    <?php echo ($ev['status'] ?? $_POST['status'] ?? 'draft') === $s ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_',' ',$s)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div class="form-group">
                        <label>Date &amp; Time *</label>
                        <input type="datetime-local" name="event_date" required
                               value="<?php echo htmlspecialchars(!empty($ev['event_date']) ? date('Y-m-d\TH:i', strtotime($ev['event_date'])) : ($_POST['event_date'] ?? '')); ?>">
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <select name="city_id">
                            <option value="">— none —</option>
                            <?php foreach ($cities as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>"
                                    <?php echo ((int)($ev['city_id'] ?? $_POST['city_id'] ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div class="form-group">
                        <label>Organiser type</label>
                        <select name="organiser_type">
                            <option value="user" <?php echo ($ev['organiser_type'] ?? 'user') === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="group" <?php echo ($ev['organiser_type'] ?? '') === 'group' ? 'selected' : ''; ?>>Group</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Organiser ID</label>
                        <input type="number" name="organiser_id" min="0"
                               value="<?php echo (int)($ev['organiser_id'] ?? $_POST['organiser_id'] ?? 0); ?>">
                    </div>
                    <div class="form-group">
                        <label>Venue / Location label</label>
                        <input type="text" name="venue_label" maxlength="255"
                               value="<?php echo htmlspecialchars($ev['venue_label'] ?? $_POST['venue_label'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:1rem;">
                    <label>Description</label>
                    <textarea name="description" rows="4"><?php echo htmlspecialchars($ev['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div class="form-group">
                        <label>Ticket type</label>
                        <select name="ticket_type">
                            <option value="none" <?php echo ($ev['ticket_type'] ?? 'none') === 'none' ? 'selected' : ''; ?>>Free / door</option>
                            <option value="simuze" <?php echo ($ev['ticket_type'] ?? '') === 'simuze' ? 'selected' : ''; ?>>Simuze</option>
                            <option value="external" <?php echo ($ev['ticket_type'] ?? '') === 'external' ? 'selected' : ''; ?>>External</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ticket price (EUR)</label>
                        <input type="number" name="ticket_price" min="0" step="0.01"
                               value="<?php echo number_format((float)($ev['ticket_price'] ?? 0), 2, '.', ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Ticket capacity</label>
                        <input type="number" name="ticket_capacity" min="0" placeholder="unlimited"
                               value="<?php echo !empty($ev['ticket_capacity']) ? (int)$ev['ticket_capacity'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Open band slots</label>
                        <input type="number" name="open_slots" min="0"
                               value="<?php echo (int)($ev['open_slots'] ?? 0); ?>">
                    </div>
                </div>

                <div style="margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $isEdit ? 'Save changes' : 'Create show'; ?>
                    </button>
                    <?php if ($isEdit && !empty($ev['slug'])): ?>
                        <a href="<?php echo htmlspecialchars($publicBase . '/show/' . $ev['slug']); ?>"
                           target="_blank" class="btn btn-outline" style="margin-left:.5rem;">View ↗</a>
                        <a href="<?php echo htmlspecialchars($publicBase . '/shows/add?edit=' . (int)$ev['id']); ?>"
                           class="btn btn-outline" style="margin-left:.5rem;">Edit on site</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if ($isEdit): ?>
        <div class="card" style="margin-top:1.5rem;padding:1.5rem;">
            <h3 style="margin:0 0 1rem;">Lineup</h3>
            <?php
            try {
                $slotStmt = $db->prepare(
                    "SELECT s.*, g.name AS group_name
                     FROM simuze_event_slots s
                     LEFT JOIN simuze_groups g ON g.id = s.group_id
                     WHERE s.event_id = ?
                     ORDER BY s.slot_order ASC, s.id ASC"
                );
                $slotStmt->execute([$id]);
                $lineup = $slotStmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) { $lineup = []; }
            ?>
            <?php if (empty($lineup)): ?>
                <p style="color:#888;">No lineup entries yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Group</th><th>Role</th><th>Confirmed</th></tr></thead>
                    <tbody>
                    <?php foreach ($lineup as $sl): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sl['group_name'] ?? 'Group #' . $sl['group_id']); ?></td>
                            <td><?php echo htmlspecialchars($sl['slot_type'] ?? ''); ?></td>
                            <td><?php echo $sl['confirmed'] ? '✓' : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return;
}

// ------------------------------------------------------------------
// List view
// ------------------------------------------------------------------
$filter_status = $_GET['filter_status'] ?? '';
$search        = trim($_GET['q'] ?? '');
$where         = ['e.deleted_at IS NULL'];
$params        = [];

if ($filter_status !== '') {
    $where[]  = 'e.status = ?';
    $params[] = $filter_status;
}
if ($search !== '') {
    $where[]  = '(e.title LIKE ? OR e.slug LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

try {
    $stmt = $db->prepare(
        "SELECT e.id, e.title, e.slug, e.event_date, e.type, e.status, e.open_slots,
                e.ticket_type, c.name AS city_name
         FROM simuze_events e
         LEFT JOIN simuze_cities c ON c.id = e.city_id
         {$whereSql}
         ORDER BY e.event_date DESC
         LIMIT 200"
    );
    $stmt->execute($params);
    $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    $events = [];
}

$status_options = ['draft','open','lineup_set','confirmed','past','cancelled'];
?>
<div class="container simuze-admin-page">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1>Shows</h1>
            <div style="display:flex;gap:.5rem;">
                <a href="<?php echo htmlspecialchars($publicBase . '/shows/add'); ?>" target="_blank" class="btn btn-outline">
                    + Add on site
                </a>
                <a href="<?php echo htmlspecialchars($baseUrl . '&action=new'); ?>" class="btn btn-primary">
                    + Add in admin
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($_GET['deleted'])): ?>
        <div class="alert alert-success">Show deleted.</div>
    <?php endif; ?>
    <?php if (!empty($_GET['notfound'])): ?>
        <div class="alert alert-error">Show not found.</div>
    <?php endif; ?>

    <div class="shows-toolbar">
        <form method="get" class="shows-toolbar-form">
            <input type="hidden" name="module" value="simuze-community">
            <input type="hidden" name="page" value="shows">
            <input type="search" name="q" class="shows-toolbar-input shows-toolbar-search" placeholder="Search…" value="<?php echo htmlspecialchars($search); ?>">
            <select name="filter_status" class="shows-toolbar-input">
                <option value="">All statuses</option>
                <?php foreach ($status_options as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $filter_status === $s ? 'selected' : ''; ?>>
                        <?php echo ucfirst(str_replace('_',' ',$s)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary shows-toolbar-btn">
                <i class="fas fa-search"></i> Filter
            </button>
        </form>
    </div>

    <div class="card shows-table-card">
        <?php if (empty($events)): ?>
            <div class="simuze-empty">
                No shows found.
                <a href="<?php echo htmlspecialchars($baseUrl . '&action=new'); ?>">Add the first one.</a>
            </div>
        <?php else: ?>
            <div class="shows-table-wrap">
            <table class="simuze-shows-table">
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th class="col-title">Title</th>
                        <th class="col-city">City</th>
                        <th class="col-date">Date</th>
                        <th class="col-status">Status</th>
                        <th class="col-tickets">Tickets</th>
                        <th class="col-slots">Slots</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $ev): ?>
                    <tr>
                        <td class="col-id"><?php echo (int)$ev['id']; ?></td>
                        <td class="col-title" title="<?php echo htmlspecialchars($ev['title']); ?>">
                            <a href="<?php echo htmlspecialchars($baseUrl . '&action=edit&id=' . (int)$ev['id']); ?>">
                                <?php echo htmlspecialchars($ev['title']); ?>
                            </a>
                        </td>
                        <td class="col-city"><?php echo htmlspecialchars($ev['city_name'] ?? '—'); ?></td>
                        <td class="col-date"><?php echo $ev['event_date'] ? date('j M Y', strtotime($ev['event_date'])) : '—'; ?></td>
                        <td class="col-status">
                            <span class="badge badge-<?php echo htmlspecialchars($ev['status']); ?>">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$ev['status']))); ?>
                            </span>
                        </td>
                        <td class="col-tickets"><?php echo htmlspecialchars(ucfirst($ev['ticket_type'] ?? 'none')); ?></td>
                        <td class="col-slots"><?php echo (int)$ev['open_slots']; ?></td>
                        <td class="col-actions">
                            <a href="<?php echo htmlspecialchars($baseUrl . '&action=edit&id=' . (int)$ev['id']); ?>"
                               class="btn btn-sm btn-icon" title="Edit"><i class="fas fa-pen" aria-hidden="true"></i></a>
                            <a href="<?php echo htmlspecialchars($publicBase . '/show/' . rawurlencode($ev['slug'] ?? '')); ?>"
                               target="_blank" class="btn btn-sm btn-icon" title="View"><i class="fas fa-external-link-alt" aria-hidden="true"></i></a>
                            <form method="POST" action="<?php echo htmlspecialchars($baseUrl); ?>"
                                  style="display:inline;" onsubmit="return confirm('Delete this show?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$ev['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-icon btn-icon-danger" title="Delete"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Toolbar: single-row filter bar matching media page pattern */
.shows-toolbar {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--background-subtle);
    border-radius: var(--radius-md);
}
form.shows-toolbar-form {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center;
    justify-content: flex-start;
    gap: 0.5rem;
    flex: 1 1 auto;
    min-width: 0;
}
form.shows-toolbar-form > * {
    flex: 0 0 auto;
}
.shows-toolbar-input {
    display: inline-block;
    height: 34px;
    padding: 0 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--text-color, #374151);
    background: var(--background-light, #fff);
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 6px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    box-sizing: border-box;
}
.shows-toolbar-input:focus {
    outline: none;
    border-color: var(--btn-default-bg, #71C1D2);
    box-shadow: 0 0 0 2px rgba(113, 193, 210, 0.2);
}
.shows-toolbar-search {
    width: 250px;
    min-width: 200px;
}
.shows-toolbar-btn {
    height: 34px;
    padding: 0 1rem;
    font-size: 0.875rem;
    line-height: 32px;
    flex-shrink: 0;
}

/* Card + table: content stays within the card, never overflows */
.shows-table-card { max-width: 100%; overflow: hidden; }
.shows-table-wrap { padding: 0; max-width: 100%; overflow-x: auto; }
.simuze-shows-table { table-layout: fixed; width: 100%; max-width: 100%; border-collapse: collapse; }
.simuze-shows-table th,
.simuze-shows-table td { padding: 0.6rem 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color, #eee); vertical-align: middle; }
.simuze-shows-table thead th { background: var(--bg-secondary, #f8f9fa); font-weight: 600; font-size: 0.8125rem; }
.simuze-shows-table tbody tr:hover { background: rgba(0,0,0,0.02); }
.simuze-shows-table tbody tr:last-child td { border-bottom: 0; }

/* Column widths */
.simuze-shows-table .col-id { width: 4%; font-size: 0.85rem; white-space: nowrap; }
.simuze-shows-table .col-title { width: 28%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.simuze-shows-table .col-title a { display: block; overflow: hidden; text-overflow: ellipsis; }
.simuze-shows-table .col-city { width: 12%; }
.simuze-shows-table .col-date { width: 13%; white-space: nowrap; }
.simuze-shows-table .col-status { width: 11%; }
.simuze-shows-table .col-tickets { width: 9%; }
.simuze-shows-table .col-slots { width: 6%; text-align: center; }
.simuze-shows-table .col-actions { width: 10%; white-space: nowrap; text-align: right; }

/* Icon action buttons */
.btn-icon { min-width: 2rem; padding: 0.25rem 0.4rem; }
.btn-icon .fas { margin: 0; }
.btn-icon-danger { color: var(--error-text, #c00) !important; }
.btn-icon-danger:hover { background: #f8d7da; color: #842029; }

/* Status badges */
.badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.75rem; font-weight: 600; }
.badge-draft { background: var(--neutral-200, #eee); color: var(--neutral-700, #333); }
.badge-open { background: var(--success-bg, #d4edda); color: var(--success-text, #155724); }
.badge-lineup_set { background: #cce5ff; color: #004085; }
.badge-confirmed { background: #d4edda; color: #155724; }
.badge-past { background: var(--neutral-200, #eee); color: var(--neutral-600, #555); }
.badge-cancelled { background: var(--error-bg, #f8d7da); color: var(--error-text, #721c24); }
</style>
