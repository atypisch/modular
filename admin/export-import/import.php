<?php
/**
 * Import handler — POST: receive uploaded bundle, run import, render report.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/csrf.php';
require_once CORE_PATH . '/functions/database.php';
require_once CORE_PATH . '/classes/Importer.php';

if (!is_logged_in() || (function_exists('is_admin') && !is_admin())) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/admin') . '/export-import?tab=import');
    exit;
}
if (!md_verify_csrf()) {
    http_response_code(403);
    echo 'Invalid CSRF token';
    exit;
}

$mode    = $_POST['mode']   ?? 'skip';
$dryrun  = !empty($_POST['dryrun']);
if ($dryrun) $mode = 'dryrun';
if (!in_array($mode, ['skip', 'overwrite', 'dryrun'], true)) $mode = 'skip';

if (empty($_FILES['bundle']) || $_FILES['bundle']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['_export_import_flash'] = [
        'type'    => 'error',
        'message' => 'No bundle uploaded (or upload error code ' . ($_FILES['bundle']['error'] ?? '?') . ').',
    ];
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/admin') . '/export-import?tab=import');
    exit;
}

$origName = $_FILES['bundle']['name'];
$tmpUp    = $_FILES['bundle']['tmp_name'];

// Move to a stable temp path so ZipArchive (and later cleanup) can use it
$dest = sys_get_temp_dir() . '/modular-import-' . bin2hex(random_bytes(6)) . '.zip';
if (!@move_uploaded_file($tmpUp, $dest)) {
    $_SESSION['_export_import_flash'] = [
        'type'    => 'error',
        'message' => 'Could not move uploaded file to temp location.',
    ];
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/admin') . '/export-import?tab=import');
    exit;
}

@set_time_limit(0);

$report   = null;
$preview  = null;
$err      = null;
$manifest = null;

try {
    $importer = new \Modular\Importer($dest);
    $manifest = $importer->validate();
    $preview  = $importer->preview();
    $report   = $importer->run($mode, (int) ($_SESSION['user_id'] ?? 0));
} catch (\Throwable $e) {
    $err = $e->getMessage();
}

@unlink($dest);

$page_title           = 'Import report';
$export_import_active = true;
require_once ADMIN_PATH . '/templates/header.php';
?>
<style>
.eim-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:1.5rem; margin-bottom:1.5rem; }
.eim-table { width:100%; border-collapse:collapse; font-size:0.92em; }
.eim-table th, .eim-table td { padding:0.5rem 0.75rem; border-bottom:1px solid #f1f5f9; text-align:left; }
.eim-table th { background:#f8fafc; font-weight:600; }
.eim-pill { display:inline-block; padding:0.1rem 0.5rem; border-radius:999px; font-size:0.78em; background:#f1f5f9; color:#334155; }
.eim-pill.eim-skip { background:#dbeafe; color:#1e40af; }
.eim-pill.eim-create { background:#dcfce7; color:#166534; }
.eim-pill.eim-update { background:#fef3c7; color:#92400e; }
.eim-flash-error { background:#fef2f2; border:1px solid #ef4444; color:#991b1b; padding:0.75rem 1rem; border-radius:6px; margin-bottom:1rem; }
</style>
<div class="container">
    <div class="page-header">
        <h1>Import report
            <?php if ($mode === 'dryrun'): ?><span class="eim-pill" style="background:#fef3c7; color:#92400e;">DRY RUN</span><?php endif; ?>
        </h1>
        <p style="color:#64748b; margin-top:0.25rem;">
            Bundle: <code><?php echo htmlspecialchars($origName); ?></code>
            &middot; Mode: <strong><?php echo htmlspecialchars($mode); ?></strong>
        </p>
    </div>

    <?php if ($err): ?>
        <div class="eim-flash-error"><strong>Import failed.</strong> <?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <?php if ($manifest): ?>
        <div class="eim-card">
            <strong>Source:</strong> <?php echo htmlspecialchars($manifest['source_url'] ?? '?'); ?>
            &middot; <strong>Exported:</strong> <?php echo htmlspecialchars($manifest['exported_at'] ?? '?'); ?>
            &middot; <strong>Tables in bundle:</strong> <?php echo count($manifest['tables'] ?? []); ?>
            <?php if (!empty($manifest['include_media'])): ?>&middot; <em>media files included</em><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($report): ?>
        <div class="eim-card">
            <h2 style="margin-top:0;">Per-table summary</h2>
            <table class="eim-table">
                <thead><tr><th>Table</th><th>Created</th><th>Updated</th><th>Skipped</th></tr></thead>
                <tbody>
                <?php foreach ($report as $name => $r): ?>
                    <?php if ($name[0] === '_') continue; /* meta keys */ ?>
                    <?php if ($name === '_media') continue; ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
                        <td><span class="eim-pill eim-create"><?php echo (int) ($r['created'] ?? 0); ?></span></td>
                        <td><span class="eim-pill eim-update"><?php echo (int) ($r['updated'] ?? 0); ?></span></td>
                        <td><span class="eim-pill eim-skip"><?php echo (int) ($r['skipped'] ?? 0); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($report['_media'])): ?>
                <p style="margin-top:1rem;">
                    <strong>Media files:</strong>
                    <span class="eim-pill eim-create"><?php echo (int) $report['_media']['copied']; ?> copied</span>
                    <span class="eim-pill eim-skip"><?php echo (int) $report['_media']['skipped']; ?> skipped</span>
                </p>
            <?php endif; ?>

            <?php if (!empty($report['_errors'])): ?>
                <h3>Errors</h3>
                <ul>
                    <?php foreach ($report['_errors'] as $msg): ?>
                        <li style="color:#991b1b;"><?php echo htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($preview): ?>
        <div class="eim-card">
            <h3 style="margin-top:0;">Pre-import preview (matched against your DB before running)</h3>
            <table class="eim-table">
                <thead><tr><th>Table</th><th>Bundle rows</th><th>New here</th><th>Conflicts (matched)</th></tr></thead>
                <tbody>
                <?php foreach ($preview as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><?php echo (int) $p['rows']; ?></td>
                        <td><?php echo (int) $p['new']; ?></td>
                        <td><?php echo !empty($p['no_dedup']) ? '<em style="color:#94a3b8;">insert-only</em>' : (int) $p['conflicts']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <p>
        <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/admin'; ?>/export-import?tab=import" class="btn">&larr; Back</a>
    </p>
</div>
<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
