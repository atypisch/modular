<?php
/**
 * Core update service – download release zip, validate, backup, replace, migrate, rollback on failure
 */

namespace Modular\Update;

class UpdateService {
    private \PDO $db;
    private VersionService $versionService;
    private MaintenanceModeService $maintenance;
    private BackupService $backup;
    private MigrationRunner $migrationRunner;
    private string $tmpPath;
    private string $corePath;
    private string $modulesPath;

    public function __construct(\PDO $db) {
        $this->db = $db;
        $this->versionService = new VersionService();
        $this->maintenance = new MaintenanceModeService();
        $this->backup = new BackupService($db);
        $this->migrationRunner = new MigrationRunner($db);
        $this->tmpPath = defined('UPDATE_TMP_PATH') ? UPDATE_TMP_PATH : (defined('MODULAR_PATH') ? MODULAR_PATH . '/updates/tmp' : '');
        $this->corePath = defined('CORE_PATH') ? dirname(CORE_PATH) . '/core' : (defined('MODULAR_PATH') ? MODULAR_PATH . '/core' : '');
        $this->modulesPath = defined('MODULAR_PATH') ? MODULAR_PATH . '/public/modules' : '';
    }

    /**
     * Run core update to a given version (tag or version string).
     * Downloads zip from GitHub, extracts, validates, backs up, replaces, runs migrations.
     */
    public function runUpdate(string $versionTo, ?int $userId = null): array {
        $current = $this->versionService->getCurrentVersion();
        $versionFrom = $current['version'];
        $release = $this->versionService->getLatestRelease();
        if ($release === null) {
            return ['success' => false, 'error' => 'Could not fetch latest release.'];
        }
        $zipUrl = $release['zipball_url'] ?? '';
        if ($zipUrl === '') {
            return ['success' => false, 'error' => 'No zip URL in release.'];
        }

        $updateId = null;
        $backupDir = null;
        $tmpDir = '';

        UpdateLog::log('Core update started', ['from' => $versionFrom, 'to' => $versionTo, 'user_id' => $userId]);

        try {
            $this->db->prepare("INSERT INTO system_updates (version_from, version_to, status, started_at) VALUES (?, ?, 'running', NOW())")->execute([$versionFrom, $versionTo]);
            $updateId = (int) $this->db->lastInsertId();

            $tmpDir = $this->tmpPath . '/' . preg_replace('/[^a-z0-9._-]/i', '_', $versionTo);
            if (is_dir($tmpDir)) {
                $this->removeDir($tmpDir);
            }
            @mkdir(dirname($tmpDir), 0755, true);
            @mkdir($tmpDir, 0755, true);

            $zipFile = $tmpDir . '/release.zip';
            $ctx = stream_context_create(['http' => ['header' => "User-Agent: Modular-CMS-Update\r\n", 'timeout' => 120]]);
            $zipContent = @file_get_contents($zipUrl, false, $ctx);
            if ($zipContent === false) {
                throw new \RuntimeException('Failed to download release zip.');
            }
            if (@file_put_contents($zipFile, $zipContent) === false) {
                throw new \RuntimeException('Failed to save zip file.');
            }

            $extractDir = $tmpDir . '/extract';
            @mkdir($extractDir, 0755, true);
            $zip = new \ZipArchive();
            if ($zip->open($zipFile) !== true) {
                throw new \RuntimeException('Invalid zip file.');
            }
            $zip->extractTo($extractDir);
            $zip->close();

            $topLevel = array_values(array_filter(scandir($extractDir), fn($d) => $d !== '.' && $d !== '..'));
            $rootDir = count($topLevel) === 1 ? $extractDir . '/' . $topLevel[0] : $extractDir;
            if (!is_dir($rootDir . '/core') || !is_dir($rootDir . '/public')) {
                throw new \RuntimeException('Invalid release structure: missing core/ or public/.');
            }

            // Optional safety: validate that the downloaded core version is newer than the current one.
            $newVersionFile = $rootDir . '/core/version.json';
            if (is_readable($newVersionFile)) {
                $json = @file_get_contents($newVersionFile);
                $data = @json_decode((string) $json, true);
                if (is_array($data) && !empty($data['version'])) {
                    $newVersion = (string) $data['version'];
                    if (VersionService::compareSemver($versionFrom, $newVersion) >= 0) {
                        throw new \RuntimeException(
                            'Downloaded core version ' . $newVersion . ' is not newer than current version ' . $versionFrom . '.'
                        );
                    }
                }
            }

            $this->maintenance->enable();
            UpdateLog::log('Maintenance mode enabled');

            $backupDir = $this->backup->createBackup('pre_update_' . $versionTo);
            if ($backupDir === null) {
                throw new \RuntimeException('Backup failed.');
            }
            UpdateLog::log('Backup created', ['path' => $backupDir]);

            if (is_dir($this->corePath)) {
                $this->copyRecursive($rootDir . '/core', $this->corePath);
            } else {
                @mkdir($this->corePath, 0755, true);
                $this->copyRecursive($rootDir . '/core', $this->corePath);
            }

            $migrated = $this->migrationRunner->runPending();
            UpdateLog::log('Migrations run', ['ran' => $migrated]);

            // Update build.json to the new version
            $this->versionService->updateBuildInfo(['version' => $versionTo, 'core' => $versionTo]);

            $this->clearCache();
            $this->maintenance->disable();
            $this->db->prepare("UPDATE system_updates SET status = 'completed', completed_at = NOW(), notes = ? WHERE id = ?")->execute(['OK', $updateId]);
            UpdateLog::log('Core update completed', ['from' => $versionFrom, 'to' => $versionTo]);

            $this->removeDir($tmpDir);
            return ['success' => true, 'version_from' => $versionFrom, 'version_to' => $versionTo];
        } catch (\Throwable $e) {
            UpdateLog::log('Core update failed', ['error' => $e->getMessage(), 'update_id' => $updateId]);
            $this->maintenance->disable();
            if ($updateId !== null) {
                $this->db->prepare("UPDATE system_updates SET status = 'failed', completed_at = NOW(), rollback_triggered = 1, notes = ? WHERE id = ?")->execute([$e->getMessage(), $updateId]);
            }
            if ($backupDir !== null && is_dir($backupDir)) {
                $this->backup->restoreFromBackup($backupDir);
                UpdateLog::log('Rollback from backup completed', ['path' => $backupDir]);
            }
            if (is_dir($tmpDir ?? '')) {
                $this->removeDir($tmpDir);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function clearCache(): void {
        $cacheDir = defined('MODULAR_PATH') ? MODULAR_PATH . '/core/storage/cache' : '';
        if ($cacheDir !== '' && is_dir($cacheDir)) {
            $this->removeDir($cacheDir);
            @mkdir($cacheDir, 0755, true);
        }
    }

    private function copyRecursive(string $src, string $dst): void {
        if (!is_dir($src)) return;
        @mkdir($dst, 0755, true);
        $dir = new \DirectoryIterator($src);
        foreach ($dir as $entry) {
            if ($entry->isDot()) continue;
            $s = $src . '/' . $entry->getFilename();
            $d = $dst . '/' . $entry->getFilename();
            if ($entry->isDir()) {
                $this->copyRecursive($s, $d);
            } else {
                @copy($s, $d);
            }
        }
    }

    private function removeDir(string $path): void {
        if (!is_dir($path)) return;
        $dir = new \DirectoryIterator($path);
        foreach ($dir as $entry) {
            if ($entry->isDot()) continue;
            $p = $path . '/' . $entry->getFilename();
            if ($entry->isDir()) {
                $this->removeDir($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($path);
    }
}
