<?php

namespace Modular\Update;

/**
 * Module update service – download module release from GitHub, backup, replace files.
 * Expects each module to have a module.json with a github_repo field.
 */
class ModuleUpdateService
{
    private \PDO $db;
    private string $modulesPath;
    private string $tmpPath;
    private string $backupPath;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
        $base = defined('MODULAR_PATH') ? MODULAR_PATH : dirname(__DIR__, 3);
        $this->modulesPath = $base . '/public/modules';
        $this->tmpPath = defined('UPDATE_TMP_PATH') ? UPDATE_TMP_PATH : ($base . '/updates/tmp');
        $this->backupPath = defined('UPDATE_BACKUP_PATH') ? UPDATE_BACKUP_PATH : ($base . '/core/storage/backups');
    }

    /**
     * Update a single module by slug.
     *
     * @return array{success:bool, error?:string, version_from?:string, version_to?:string}
     */
    public function run(string $slug): array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return ['success' => false, 'error' => 'Empty module slug.'];
        }

        $moduleDir = $this->modulesPath . '/' . $slug;
        if (!is_dir($moduleDir)) {
            return ['success' => false, 'error' => "Module directory not found for '{$slug}'."];
        }

        $metaFile = $moduleDir . '/module.json';
        if (!is_readable($metaFile)) {
            return ['success' => false, 'error' => "module.json not found or not readable for '{$slug}'."];
        }
        $meta = @json_decode((string) @file_get_contents($metaFile), true);
        if (!is_array($meta)) {
            return ['success' => false, 'error' => "module.json is invalid JSON for '{$slug}'."];
        }

        $repo = (string) ($meta['github_repo'] ?? '');
        if ($repo === '') {
            return ['success' => false, 'error' => "github_repo is not configured for module '{$slug}'."];
        }

        $currentVersion = (string) ($meta['version'] ?? '0.0.0');

        $versionService = new VersionService(null, $repo);
        $latest = $versionService->getLatestRelease();
        if ($latest === null) {
            $err = $versionService->getLastError() ?: 'Could not fetch latest module release.';
            return ['success' => false, 'error' => $err];
        }

        $versionTo = (string) $latest['version'];
        $zipUrl = (string) ($latest['zipball_url'] ?? '');
        if ($zipUrl === '') {
            return ['success' => false, 'error' => 'Latest release does not expose a zipball_url.'];
        }

        $label = 'module_' . $slug . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $versionTo);
        $tmpDir = rtrim($this->tmpPath, '/') . '/' . $label;
        $this->removeDir($tmpDir);
        @mkdir(dirname($tmpDir), 0755, true);
        @mkdir($tmpDir, 0755, true);

        $zipFile = $tmpDir . '/release.zip';
        if (!$this->downloadZip($zipUrl, $zipFile)) {
            $this->removeDir($tmpDir);
            return ['success' => false, 'error' => 'Failed to download module release zip.'];
        }

        $extractDir = $tmpDir . '/extract';
        @mkdir($extractDir, 0755, true);
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            $this->removeDir($tmpDir);
            return ['success' => false, 'error' => 'Invalid module release zip.'];
        }
        $zip->extractTo($extractDir);
        $zip->close();

        $top = array_values(array_filter(scandir($extractDir) ?: [], static function ($d) {
            return $d !== '.' && $d !== '..';
        }));
        $rootDir = count($top) === 1 ? $extractDir . '/' . $top[0] : $extractDir;

        $newModuleDir = $rootDir;
        if (!is_file($newModuleDir . '/module.php')) {
            $candidate = $rootDir . '/' . $slug;
            if (is_dir($candidate) && is_file($candidate . '/module.php')) {
                $newModuleDir = $candidate;
            } else {
                $this->removeDir($tmpDir);
                return ['success' => false, 'error' => 'Could not locate module.php in downloaded release.'];
            }
        }

        $backupBase = rtrim($this->backupPath, '/');
        @mkdir($backupBase, 0755, true);
        $backupDir = $backupBase . '/module_' . $slug . '_' . date('Y-m-d_H-i-s');
        @mkdir($backupDir, 0755, true);
        if (!is_dir($backupDir)) {
            $this->removeDir($tmpDir);
            return ['success' => false, 'error' => 'Failed to create module backup directory.'];
        }
        $this->copyRecursive($moduleDir, $backupDir);

        $this->removeDir($moduleDir);
        @mkdir($moduleDir, 0755, true);
        $this->copyRecursive($newModuleDir, $moduleDir);

        $this->removeDir($tmpDir);
        UpdateLog::log('Module updated', [
            'slug' => $slug,
            'from' => $currentVersion,
            'to' => $versionTo,
        ]);

        return [
            'success' => true,
            'version_from' => $currentVersion,
            'version_to' => $versionTo,
        ];
    }

    private function downloadZip(string $url, string $dest): bool
    {
        $headers = [
            'User-Agent: Modular-CMS-Module-Update',
            'Accept: application/vnd.github.v3+json',
        ];
        if (defined('UPDATE_GITHUB_TOKEN') && UPDATE_GITHUB_TOKEN !== '') {
            $headers[] = 'Authorization: Bearer ' . UPDATE_GITHUB_TOKEN;
        }

        $data = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $data = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($data === false || $status !== 200) {
                return false;
            }
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers) . "\r\n",
                    'timeout' => 120,
                ],
            ]);
            $data = @file_get_contents($url, false, $ctx);
            if ($data === false) {
                return false;
            }
        }

        return @file_put_contents($dest, $data) !== false;
    }

    private function copyRecursive(string $src, string $dst): void
    {
        if (!is_dir($src)) {
            return;
        }
        @mkdir($dst, 0755, true);
        $dir = new \DirectoryIterator($src);
        foreach ($dir as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            $s = $src . '/' . $entry->getFilename();
            $d = $dst . '/' . $entry->getFilename();
            if ($entry->isDir()) {
                $this->copyRecursive($s, $d);
            } else {
                @copy($s, $d);
            }
        }
    }

    private function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $dir = new \DirectoryIterator($path);
        foreach ($dir as $entry) {
            if ($entry->isDot()) {
                continue;
            }
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

