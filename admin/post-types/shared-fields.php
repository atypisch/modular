<?php
/**
 * Shared Fields Overview — manage reusable field definitions across post types.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$page_title = 'Shared Fields';
$post_types_active = true;

$db = $GLOBALS['db'];
$adminUrl = defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin';

// Load shared fields
$sharedFields = $db->query(
    "SELECT id, name, type, definition, created_at, updated_at FROM shared_fields WHERE deleted_at IS NULL ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

// Build linked post types map
$postTypes = $db->query(
    "SELECT id, name, slug, `schema` FROM post_types WHERE deleted_at IS NULL AND `schema` IS NOT NULL"
)->fetchAll(PDO::FETCH_ASSOC);

$linkedMap = []; // shared_id => [{id, name, slug}]
foreach ($postTypes as $pt) {
    $schema = json_decode($pt['schema'], true);
    if (!is_array($schema) || empty($schema['fields'])) continue;
    foreach ($schema['fields'] as $field) {
        if (isset($field['_shared_id'])) {
            $sid = (int)$field['_shared_id'];
            if (!isset($linkedMap[$sid])) $linkedMap[$sid] = [];
            $linkedMap[$sid][] = ['id' => (int)$pt['id'], 'name' => $pt['name'], 'slug' => $pt['slug']];
        }
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header" style="margin-bottom: var(--spacing-lg);">
        <div class="flex justify-between items-center">
            <div>
                <h1>Shared Fields</h1>
                <p class="text-muted" style="margin-top: 0.25rem;">Velden en field groups die gedeeld worden tussen post types.</p>
            </div>
            <a href="<?php echo $adminUrl; ?>/post-types" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Terug naar Post Types</a>
        </div>
    </div>

    <?php if (empty($sharedFields)): ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <i class="fas fa-share-alt" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                <p class="text-muted">Nog geen gedeelde velden. Deel een veld via de Field Builder bij een post type.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <table class="table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Naam</th>
                        <th>Type</th>
                        <th>Gekoppelde post types</th>
                        <th>Aangemaakt</th>
                        <th style="width: 120px;">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sharedFields as $sf):
                        $linked = $linkedMap[(int)$sf['id']] ?? [];
                        $def = json_decode($sf['definition'], true);
                        $fieldType = is_array($def) ? ($def['type'] ?? '?') : '?';
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($sf['name']); ?></strong>
                            <br><span class="text-muted" style="font-size: 0.8rem;">Field: <?php echo htmlspecialchars($fieldType); ?></span>
                        </td>
                        <td>
                            <span class="badge"><?php echo $sf['type'] === 'field_group' ? 'Field Group' : 'Field'; ?></span>
                        </td>
                        <td>
                            <?php if (empty($linked)): ?>
                                <span class="text-muted">Geen</span>
                            <?php else: ?>
                                <?php foreach ($linked as $lpt): ?>
                                    <a href="<?php echo $adminUrl; ?>/field-builder/?id=<?php echo $lpt['id']; ?>" class="btn btn-secondary btn-sm" style="margin: 0.1rem; font-size: 0.75rem; padding: 0.1rem 0.4rem;"><?php echo htmlspecialchars($lpt['name']); ?></a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td><span class="text-muted" style="font-size: 0.85rem;"><?php echo date('d M Y', strtotime($sf['created_at'])); ?></span></td>
                        <td>
                            <?php if (!empty($linked)): ?>
                                <a href="<?php echo $adminUrl; ?>/field-builder/?id=<?php echo $linked[0]['id']; ?>" class="btn btn-secondary btn-sm" title="Bewerk in field builder"><i class="fas fa-edit"></i></a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-danger btn-sm btn-delete-shared" data-id="<?php echo (int)$sf['id']; ?>" data-name="<?php echo htmlspecialchars($sf['name']); ?>" title="Verwijder"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
(function() {
    var adminUrl = <?php echo json_encode($adminUrl); ?>;
    document.querySelectorAll('.btn-delete-shared').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var name = this.getAttribute('data-name');
            var msg = 'Gedeeld veld "' + name + '" verwijderen?\n\nAlle gekoppelde velden worden losgekoppeld (maar behouden hun huidige definitie).';
            var p = window.ModularModal && window.ModularModal.confirm
                ? window.ModularModal.confirm(msg)
                : Promise.resolve(confirm(msg));
            Promise.resolve(p).then(function(ok) {
                if (!ok) return;
                var payload = new FormData();
                payload.append('_method', 'DELETE');
                payload.append('id', id);
                var csrfInput = document.querySelector('meta[name="csrf-token"]');
                if (csrfInput) payload.append('_csrf_token', csrfInput.getAttribute('content'));
                fetch(adminUrl + '/post-types/shared-fields-api.php', {
                    method: 'POST',
                    body: payload,
                    credentials: 'same-origin'
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.success) {
                        window.location.reload();
                    } else {
                        window.ModularModal && window.ModularModal.alert('Fout: ' + (data.error || 'Onbekende fout'));
                    }
                })
                .catch(function(err) {
                    window.ModularModal && window.ModularModal.alert('Request mislukt.');
                });
            });
        });
    });
})();
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
