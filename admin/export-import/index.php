<?php
/**
 * Export / Import — Modular CMS content portability.
 *
 * Tabs:
 *   ?tab=export  (default)
 *   ?tab=import
 *   ?tab=history
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/database.php';
require_once CORE_PATH . '/functions/csrf.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/admin') . '/auth/login.php');
    exit;
}
// Page is visible to any logged-in admin user (matches /admin/users/, /admin/posts/ convention).
// State-changing actions in export.php / import.php enforce is_admin() separately.
$is_admin_user = function_exists('is_admin') && is_admin();

$page_title       = 'Export / Import';
$export_import_active = true;

$tab = $_GET['tab'] ?? 'export';
if (!in_array($tab, ['export', 'import', 'history'], true)) $tab = 'export';

$registry = function_exists('md_export_registry') ? md_export_registry() : [];

// Capture flash from import handler
$flash = $_SESSION['_export_import_flash'] ?? null;
unset($_SESSION['_export_import_flash']);

require_once ADMIN_PATH . '/templates/header.php';
?>
<style>
.eim-tabs { display:flex; gap:0; border-bottom:1px solid var(--border-color, #e5e7eb); margin-bottom:1.5rem; }
.eim-tabs a { padding:0.75rem 1.25rem; text-decoration:none; color:#555; border-bottom:2px solid transparent; }
.eim-tabs a.active { color:#111; border-bottom-color:#2563eb; font-weight:600; }
.eim-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:1.5rem; margin-bottom:1.5rem; }
.eim-table { width:100%; border-collapse:collapse; font-size:0.92em; }
.eim-table th, .eim-table td { padding:0.5rem 0.75rem; border-bottom:1px solid #f1f5f9; text-align:left; }
.eim-table th { background:#f8fafc; font-weight:600; }
.eim-pill { display:inline-block; padding:0.1rem 0.5rem; border-radius:999px; font-size:0.78em; background:#f1f5f9; color:#334155; }
.eim-radio-group { display:flex; flex-direction:column; gap:0.5rem; margin:0.75rem 0; }
.eim-radio-group label { display:flex; gap:0.5rem; align-items:start; cursor:pointer; padding:0.5rem 0.75rem; border:1px solid #e5e7eb; border-radius:6px; }
.eim-radio-group label:hover { background:#f8fafc; }
.eim-summary pre { background:#0f172a; color:#e2e8f0; padding:1rem; border-radius:6px; overflow:auto; font-size:0.85em; }
.eim-flash-success { background:#ecfdf5; border:1px solid #10b981; color:#065f46; padding:0.75rem 1rem; border-radius:6px; margin-bottom:1rem; }
.eim-flash-error { background:#fef2f2; border:1px solid #ef4444; color:#991b1b; padding:0.75rem 1rem; border-radius:6px; margin-bottom:1rem; }
</style>

<div class="container">
    <div class="page-header"><h1>Export / Import</h1></div>

    <nav class="eim-tabs">
        <a href="?tab=export"  class="<?php echo $tab==='export'  ? 'active':''; ?>">Export</a>
        <a href="?tab=import"  class="<?php echo $tab==='import'  ? 'active':''; ?>">Import</a>
        <a href="?tab=history" class="<?php echo $tab==='history' ? 'active':''; ?>">History</a>
    </nav>

    <?php if ($flash): ?>
        <div class="eim-flash-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <?php if (!$is_admin_user): ?>
        <?php
        $diag_uid     = (int) ($_SESSION['user_id'] ?? 0);
        $diag_session = $_SESSION['user_role'] ?? '(none)';
        $diag_users_role  = '(?)';
        $diag_userroles   = [];
        try {
            $r = md_db_row('SELECT role FROM users WHERE id = ?', [$diag_uid]);
            if ($r) $diag_users_role = $r['role'] ?? '(null)';
            $diag_userroles = md_db_query(
                'SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?',
                [$diag_uid]
            );
        } catch (\Throwable $e) { /* silent */ }
        $userroles_str = $diag_userroles
            ? implode(', ', array_map(fn($x) => $x['name'], $diag_userroles))
            : '(no rows in user_roles for this user)';
        $jwt = !empty($_COOKIE['accessToken']) ? 'present'
             : (!empty($_COOKIE['simuze_jwt_token']) ? 'present (legacy)' : 'absent');
        ?>
        <div class="eim-flash-error">
            <strong>Administrator role required.</strong>
            Export and import touch every table in the database, so they're admin-only.
            <table style="margin-top:0.75rem; border-collapse:collapse; font-size:0.92em;">
                <tr><td style="padding:2px 12px 2px 0;">Session <code>user_id</code></td><td><code><?php echo $diag_uid; ?></code></td></tr>
                <tr><td style="padding:2px 12px 2px 0;">Session <code>user_role</code></td><td><code><?php echo htmlspecialchars($diag_session); ?></code> &larr; <em>this is what <code>is_admin()</code> reads</em></td></tr>
                <tr><td style="padding:2px 12px 2px 0;"><code>users.role</code> (column)</td><td><code><?php echo htmlspecialchars($diag_users_role); ?></code></td></tr>
                <tr><td style="padding:2px 12px 2px 0;"><code>user_roles</code> &rarr; <code>roles.name</code></td><td><code><?php echo htmlspecialchars($userroles_str); ?></code> &larr; <em>this is what login.php reads</em></td></tr>
                <tr><td style="padding:2px 12px 2px 0;">Simezu JWT cookie</td><td><code><?php echo $jwt; ?></code></td></tr>
            </table>
            <p style="margin-top:0.75rem;"><strong>To fix on staging</strong> (one-time, run as DB admin):</p>
            <pre style="background:#0f172a; color:#e2e8f0; padding:0.75rem; border-radius:6px; font-size:0.85em; overflow:auto;">INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT <?php echo $diag_uid; ?>, id FROM roles WHERE name = 'admin';
-- then log out and log back in</pre>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'export'): ?>
        <div class="eim-card">
            <h2 style="margin-top:0;">Download a content bundle</h2>
            <p>Export all registered tables (core + active modules) into a single <code>.zip</code> bundle that can be imported into another Modular install.</p>

            <form method="post" action="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/admin'; ?>/export-import/export.php">
                <?php echo md_csrf_field(); ?>
                <label style="display:flex; gap:0.5rem; align-items:center; padding:0.75rem; background:#f8fafc; border-radius:6px; margin:1rem 0;">
                    <input type="checkbox" name="include_media" value="1">
                    <span><strong>Include media files</strong> &mdash; bundle <code>public/uploads/media/</code> into the zip (larger, but self-sufficient).</span>
                </label>
                <button type="submit" class="btn btn-primary" <?php echo $is_admin_user ? '' : 'disabled'; ?>>
                    <i class="fas fa-download"></i> Download bundle
                </button>
            </form>
        </div>

        <div class="eim-card">
            <h3 style="margin-top:0;">Tables included in this export</h3>
            <p style="color:#64748b;">The list below is built from <code>md_export_register()</code> calls across core and active modules. Tables not in this list are NOT exported.</p>
            <?php if (empty($registry)): ?>
                <p><em>No tables registered.</em></p>
            <?php else: ?>
                <table class="eim-table">
                    <thead><tr><th>Name</th><th>Table</th><th>Source</th><th>Natural key</th></tr></thead>
                    <tbody>
                    <?php foreach ($registry as $name => $cfg): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($cfg['table']); ?></code></td>
                            <td><span class="eim-pill"><?php echo htmlspecialchars($cfg['module'] ?? 'core'); ?></span></td>
                            <td><?php echo $cfg['natural_key'] ? '<code>' . htmlspecialchars(implode(', ', $cfg['natural_key'])) . '</code>' : '<em style="color:#94a3b8;">insert-only</em>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php elseif ($tab === 'import'): ?>
        <div class="eim-card">
            <h2 style="margin-top:0;">Upload a bundle</h2>
            <p>Upload a <code>.zip</code> bundle exported from another Modular install. Choose how to handle existing rows.</p>

            <form method="post" action="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/admin'; ?>/export-import/import.php" enctype="multipart/form-data">
                <?php echo md_csrf_field(); ?>

                <p>
                    <label for="bundle_file"><strong>Bundle file</strong></label><br>
                    <input type="file" name="bundle" id="bundle_file" accept=".zip" required>
                </p>

                <div class="eim-radio-group">
                    <label>
                        <input type="radio" name="mode" value="skip" checked>
                        <span><strong>Skip duplicates</strong> &mdash; existing rows (matched by natural key) are kept; only new rows are inserted.</span>
                    </label>
                    <label>
                        <input type="radio" name="mode" value="overwrite">
                        <span><strong>Overwrite duplicates</strong> &mdash; existing rows are updated with the imported values. Destructive but useful for syncing.</span>
                    </label>
                </div>

                <label style="display:flex; gap:0.5rem; align-items:center; padding:0.5rem 0.75rem; background:#fffbeb; border:1px solid #fde68a; border-radius:6px; margin-bottom:1rem;">
                    <input type="checkbox" name="dryrun" value="1">
                    <span><strong>Dry run</strong> &mdash; report what would happen without writing to the database. Wraps the import in a transaction and rolls back at the end.</span>
                </label>

                <button type="submit" class="btn btn-primary" <?php echo $is_admin_user ? '' : 'disabled'; ?>>
                    <i class="fas fa-upload"></i> Run import
                </button>
            </form>
        </div>

    <?php elseif ($tab === 'history'): ?>
        <div class="eim-card">
            <h2 style="margin-top:0;">Import history</h2>
            <?php
            $logs = [];
            try {
                $logs = md_db_query('SELECT id, imported_by, source_url, bundle_filename, mode, created_at
                                     FROM import_log ORDER BY created_at DESC LIMIT 50');
            } catch (\Throwable $e) {
                echo '<p style="color:#991b1b;">Could not read import_log: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
            <?php if (empty($logs)): ?>
                <p><em>No imports yet.</em></p>
            <?php else: ?>
                <table class="eim-table">
                    <thead><tr><th>When</th><th>Bundle</th><th>Source</th><th>Mode</th><th>By</th></tr></thead>
                    <tbody>
                    <?php foreach ($logs as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td><code><?php echo htmlspecialchars($row['bundle_filename'] ?? ''); ?></code></td>
                            <td><?php echo htmlspecialchars($row['source_url'] ?? ''); ?></td>
                            <td><span class="eim-pill"><?php echo htmlspecialchars($row['mode']); ?></span></td>
                            <td>#<?php echo (int) $row['imported_by']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
