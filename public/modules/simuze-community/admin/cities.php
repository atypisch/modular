<?php
/**
 * Simuze Community — Cities admin
 */
$db = $GLOBALS['db'] ?? null;
if (!$db) { echo '<p>Database unavailable.</p>'; return; }

$baseUrl    = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=cities';
$csrf_token = function_exists('md_csrf_token') ? md_csrf_token() : '';
$publicBase = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$msg    = '';

// Add city
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('md_verify_csrf') && md_verify_csrf($_POST['_csrf'] ?? '')) {
        $name    = trim($_POST['name'] ?? '');
        $country = trim($_POST['country'] ?? '');
        if ($name !== '' && class_exists('EventRepository')) {
            EventRepository::findOrCreateCity($name, $country);
            header('Location: ' . $baseUrl . '&added=1');
            exit;
        }
    }
}

// Delete
if ($action === 'delete' && $id > 0) {
    if (function_exists('md_verify_csrf') && md_verify_csrf($_GET['_csrf'] ?? '')) {
        $db->prepare("DELETE FROM simuze_cities WHERE id = ?")->execute([$id]);
        header('Location: ' . $baseUrl . '&deleted=1');
        exit;
    }
}

// Fetch
try {
    $stmt = $db->query(
        "SELECT c.*, COUNT(e.id) AS show_count, COUNT(v.id) AS venue_count
         FROM simuze_cities c
         LEFT JOIN simuze_events e ON e.city_id = c.id AND e.deleted_at IS NULL
         LEFT JOIN simuze_venues v ON v.city_id = c.id AND v.deleted_at IS NULL
         GROUP BY c.id
         ORDER BY c.name ASC
         LIMIT 200"
    );
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $cities = [];
}
?>

<div class="container">
  <div class="page-header">
    <h1>Cities</h1>
  </div>

  <?php if (!empty($_GET['added'])): ?><div class="alert alert-success">City added.</div><?php endif; ?>
  <?php if (!empty($_GET['deleted'])): ?><div class="alert alert-success">City deleted.</div><?php endif; ?>

  <!-- Add city form -->
  <div class="card" style="margin-bottom:24px;">
    <h3>Add City</h3>
    <form method="post" action="<?php echo $baseUrl; ?>&action=add" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
      <?php if (function_exists('md_csrf_field')) echo md_csrf_field(); ?>
      <div class="form-group" style="margin:0;">
        <label>City name</label>
        <input type="text" name="name" required placeholder="Amsterdam">
      </div>
      <div class="form-group" style="margin:0;">
        <label>Country</label>
        <input type="text" name="country" placeholder="Netherlands">
      </div>
      <button type="submit" class="button">Add</button>
    </form>
  </div>

  <div class="card">
    <?php if (empty($cities)): ?>
      <p>No cities yet. Cities are created automatically when a show is started.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>City</th>
            <th>Country</th>
            <th>Shows</th>
            <th>Venues</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cities as $c): ?>
            <tr>
              <td><?php echo (int) $c['id']; ?></td>
              <td><a href="<?php echo $publicBase; ?>/city/<?php echo htmlspecialchars($c['slug']); ?>" target="_blank"><?php echo htmlspecialchars($c['name']); ?></a></td>
              <td><?php echo htmlspecialchars($c['country'] ?? ''); ?></td>
              <td><?php echo (int) $c['show_count']; ?></td>
              <td><?php echo (int) $c['venue_count']; ?></td>
              <td>
                <a href="<?php echo $baseUrl; ?>&action=delete&id=<?php echo (int) $c['id']; ?>&_csrf=<?php echo urlencode($csrf_token); ?>"
                   class="button button-sm button-danger"
                   onclick="return confirm('Delete this city? Shows and venues referencing it will be unlinked.')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
