<?php
/**
 * Simuze Community — Venues admin
 */
$db = $GLOBALS['db'] ?? null;
if (!$db) { echo '<p>Database unavailable.</p>'; return; }

$baseUrl    = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=venues';
$csrf_token = function_exists('md_csrf_token') ? md_csrf_token() : '';

// Actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($action === 'delete' && $id > 0) {
    if (function_exists('md_verify_csrf') && md_verify_csrf($_GET['_csrf'] ?? '')) {
        $db->prepare("UPDATE simuze_venues SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
        header('Location: ' . $baseUrl . '&deleted=1');
        exit;
    }
}

// Fetch venues
try {
    $stmt = $db->query(
        "SELECT v.*, c.name AS city_name
         FROM simuze_venues v
         LEFT JOIN simuze_cities c ON c.id = v.city_id
         WHERE v.deleted_at IS NULL
         ORDER BY v.name ASC
         LIMIT 200"
    );
    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $venues = [];
}

$type_labels = ['diy_space'=>'DIY Space','rehearsal_space'=>'Rehearsal','studio'=>'Studio','house'=>'House','bar'=>'Bar','gallery'=>'Gallery','collective'=>'Collective','basement'=>'Basement','outdoor'=>'Outdoor','other'=>'Other'];
?>

<div class="container">
  <div class="page-header">
    <h1>Venues &amp; Spaces</h1>
  </div>

  <?php if (!empty($_GET['deleted'])): ?>
    <div class="alert alert-success">Venue deleted.</div>
  <?php endif; ?>

  <div class="card">
    <?php if (empty($venues)): ?>
      <p>No venues found.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>City</th>
            <th>Capacity</th>
            <th>Public</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($venues as $v): ?>
            <tr>
              <td><?php echo (int) $v['id']; ?></td>
              <td><?php echo htmlspecialchars($v['name']); ?></td>
              <td><?php echo htmlspecialchars($type_labels[$v['type']] ?? $v['type']); ?></td>
              <td><?php echo htmlspecialchars($v['city_name'] ?? '—'); ?></td>
              <td><?php echo $v['capacity'] ? (int) $v['capacity'] : '—'; ?></td>
              <td><?php echo $v['is_public'] ? 'Yes' : 'No'; ?></td>
              <td>
                <a href="<?php echo $baseUrl; ?>&action=delete&id=<?php echo (int) $v['id']; ?>&_csrf=<?php echo urlencode($csrf_token); ?>"
                   class="button button-sm button-danger"
                   onclick="return confirm('Delete this venue?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
