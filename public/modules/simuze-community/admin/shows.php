<?php
/**
 * Simuze Community — Shows admin
 * List, view, delete events. Create is via public /start-a-show.
 */
$db = $GLOBALS['db'] ?? null;
if (!$db) { echo '<p>Database unavailable.</p>'; return; }

$baseUrl    = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=shows';
$publicBase = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';

// Actions
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($action === 'delete' && $id > 0) {
    $csrf = $_GET['_csrf'] ?? '';
    if (function_exists('md_verify_csrf') && md_verify_csrf($csrf)) {
        $db->prepare("UPDATE simuze_events SET deleted_at = NOW(), status = 'cancelled' WHERE id = ?")->execute([$id]);
        header('Location: ' . $baseUrl . '&deleted=1');
        exit;
    }
}

// Fetch events
$filter_status = $_GET['filter_status'] ?? '';
$filter_sql    = $filter_status !== '' ? "AND e.status = " . $db->quote($filter_status) : '';
try {
    $stmt = $db->query(
        "SELECT e.*, c.name AS city_name
         FROM simuze_events e
         LEFT JOIN simuze_cities c ON c.id = e.city_id
         WHERE e.deleted_at IS NULL {$filter_sql}
         ORDER BY e.event_date DESC
         LIMIT 100"
    );
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $events = [];
}

$csrf_token = function_exists('md_csrf_token') ? md_csrf_token() : '';
$status_options = ['open','lineup_set','confirmed','past','cancelled','draft'];
?>

<div class="container">
  <div class="page-header">
    <h1>Shows</h1>
    <a href="<?php echo $publicBase; ?>/start-a-show" target="_blank" class="button">
      <i class="fas fa-bolt"></i> Start a Show
    </a>
  </div>

  <?php if (!empty($_GET['deleted'])): ?>
    <div class="alert alert-success">Show deleted.</div>
  <?php endif; ?>

  <!-- Filters -->
  <form method="get" class="admin-filter-bar">
    <input type="hidden" name="module" value="simuze-community">
    <input type="hidden" name="page" value="shows">
    <select name="filter_status" onchange="this.form.submit()">
      <option value="">All statuses</option>
      <?php foreach ($status_options as $s): ?>
        <option value="<?php echo $s; ?>" <?php echo $filter_status === $s ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $s)); ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <div class="card">
    <?php if (empty($events)): ?>
      <p>No shows found.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>City</th>
            <th>Date</th>
            <th>Type</th>
            <th>Status</th>
            <th>Open slots</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($events as $ev): ?>
            <tr>
              <td><?php echo (int) $ev['id']; ?></td>
              <td>
                <a href="<?php echo $publicBase; ?>/show/<?php echo htmlspecialchars($ev['slug']); ?>" target="_blank">
                  <?php echo htmlspecialchars($ev['title']); ?>
                </a>
              </td>
              <td><?php echo htmlspecialchars($ev['city_name'] ?? '—'); ?></td>
              <td><?php echo $ev['event_date'] ? date('j M Y', strtotime($ev['event_date'])) : '—'; ?></td>
              <td><?php echo htmlspecialchars($ev['type']); ?></td>
              <td><span class="badge badge-<?php echo htmlspecialchars($ev['status']); ?>"><?php echo htmlspecialchars(str_replace('_', ' ', $ev['status'])); ?></span></td>
              <td><?php echo (int) $ev['open_slots']; ?></td>
              <td>
                <a href="<?php echo $publicBase; ?>/show/<?php echo htmlspecialchars($ev['slug']); ?>" target="_blank" class="button button-sm">View</a>
                <a href="<?php echo $baseUrl; ?>&action=delete&id=<?php echo (int) $ev['id']; ?>&_csrf=<?php echo urlencode($csrf_token); ?>"
                   class="button button-sm button-danger"
                   onclick="return confirm('Delete this show?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
