<?php
/**
 * Simuze Community – Settings admin
 * Included by admin/module-page.php when module=simuze-community&page=settings
 *
 * This page is a starting point for configuring the module.
 * Later we can persist options in a dedicated table or in the modules table.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = $GLOBALS['db'] ?? null;

// Simple placeholder form – does not persist yet, but shows how options UI looks.
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: wire up real persistence (e.g. simuze_community_settings table)
    $message = 'Settings were submitted. Persistence layer not implemented yet.';
}
?>
<div class="container">
    <div class="page-header">
        <h1>Simuze Community Settings</h1>
        <p>Configure global options for the Simuze Community module.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>

                <div class="form-group">
                    <label for="groups_enabled">Enable groups</label>
                    <select id="groups_enabled" name="groups_enabled" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                    <div class="help-text">Toggle all community group functionality on or off.</div>
                </div>

                <div class="form-group">
                    <label for="messages_enabled">Enable messages</label>
                    <select id="messages_enabled" name="messages_enabled" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                    <div class="help-text">Toggle private messaging between users.</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

