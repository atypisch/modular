<?php
/**
 * System Update API controller.
 *
 * One central upgrade point for the Modular core: reports the installed version,
 * checks the GitHub release that is the deployable SSOT, and runs the update
 * (download release zip -> back up -> replace -> migrate) via the existing
 * Modular\Update services. Powers the admin SPA "System update" screen.
 */

namespace Modular\API;

class UpdateController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $core = defined('CORE_PATH') ? CORE_PATH : dirname(__DIR__);
        // The JSON API has its own bootstrap and doesn't load admin/bootstrap.php,
        // so the update config (UPDATE_CORE_GITHUB_REPO, paths) may be undefined.
        // Load it here — it guards on MODULAR_PATH, so define that first.
        if (!defined('MODULAR_PATH')) { define('MODULAR_PATH', dirname($core)); }
        if (!defined('UPDATE_CORE_GITHUB_REPO') && is_file($core . '/config/update.php')) {
            require_once $core . '/config/update.php';
        }
        // Update services live in core/classes/Update/ but the PSR-4 prefix maps
        // Modular\ -> core/, so the autoloader can't find Modular\Update\*; require them.
        $dir = $core . '/classes/Update/';
        foreach (['UpdateLog', 'VersionService', 'MaintenanceModeService', 'BackupService', 'MigrationRunner', 'UpdateService'] as $c) {
            $f = $dir . $c . '.php';
            if (is_file($f)) { require_once $f; }
        }
    }

    /** GET /system/update — installed version, latest release, availability + history. */
    public function status() {
        $vs = new \Modular\Update\VersionService();
        $current = $vs->getCurrentVersion();
        $latest = $vs->getLatestRelease();
        $available = false;
        if (is_array($latest)) {
            $available = \Modular\Update\VersionService::compareSemver((string) $current['version'], (string) $latest['version']) < 0;
        }
        $history = [];
        try {
            $stmt = $this->db->query("SELECT id, version_from, version_to, status, started_at, completed_at FROM system_updates ORDER BY id DESC LIMIT 10");
            $history = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) { /* table optional */ }
        return [
            'current'          => $current,
            'latest'           => $latest,
            'update_available' => $available,
            'check_error'      => is_array($latest) ? null : $vs->getLastError(),
            'repo'             => defined('UPDATE_CORE_GITHUB_REPO') ? UPDATE_CORE_GITHUB_REPO : '',
            'mode'             => defined('UPDATE_MODE') ? UPDATE_MODE : 'production',
            'history'          => $history,
        ];
    }

    /** POST /system/update — run the core update to the latest release. */
    public function run() {
        $vs = new \Modular\Update\VersionService();
        $latest = $vs->getLatestRelease();
        if (!is_array($latest)) {
            return ['error' => 'Could not reach the update server: ' . ($vs->getLastError() ?: 'unknown error'), 'code' => 502];
        }
        $current = $vs->getCurrentVersion();
        if (\Modular\Update\VersionService::compareSemver((string) $current['version'], (string) $latest['version']) >= 0) {
            return ['error' => 'Already up to date (v' . $current['version'] . ').', 'code' => 400];
        }
        $userId = isset($GLOBALS['current_user']['user_id']) ? (int) $GLOBALS['current_user']['user_id'] : null;
        $svc = new \Modular\Update\UpdateService($this->db);
        $res = $svc->runUpdate((string) $latest['version'], $userId);
        if (empty($res['success'])) {
            return ['error' => $res['error'] ?? 'Update failed.', 'code' => 500];
        }
        return $res;
    }
}
