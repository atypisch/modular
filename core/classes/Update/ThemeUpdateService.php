<?php

namespace Modular\Update;

/**
 * Theme update service – download theme release from GitHub, backup, replace files.
 * Expects each theme to have a theme.json with version and optional github_repo.
 */
class ThemeUpdateService
{
    private string $themesPath;
    private string $tmpPath;
    private string $backupPath;

    public function __construct()
    {
        $base = defined('MODULAR_PATH') ? MODULAR_PATH : dirname(__DIR__, 3);
        $this->themesPath = $base . '/public/themes';
        $this->tmpPath = defined('UPDATE_TMP_PATH') ? UPDATE_TMP_PATH : ($base . '/updates/tmp');
        $this->backupPath = defined('UPDATE_BACKUP_PATH') ? UPDATE_BACKUP_PATH : ($base . '/core/storage/backups');
    }

    /**
     * Update a single theme by slug.
     *
     * @return array{success:bool, error?:string, version_from?:string, version_to?:string}
     */
    public function run(string $slug): array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return ['success' => false, 'error' => 'Empty theme slug.'];
        }

        $themeDir = $this->themesPath . '/' . $slug;
        if (!is_dir($themeDir)) {
            return ['success' => false, 'error' => "Theme directory not found for '{$slug}'."];
        }

        $metaFile = $themeDir . '/theme.json';
        if (!is_readable($metaFile)) {
            return ['success' => false, 'error' => "theme.json not found or not readable for '{$slug}'."];
        }
        $meta = @json_decode((string) @file_get_contents($metaFile), true);
        if (!is_array($meta)) {
            return ['success' => false, 'error' => "theme.json is invalid JSON for '{$slug}'."];
        }

        $repo = (string) ($meta['github_repo'] ?? '');
        if ($repo === '') {
            return ['success' => false, 'error' => "github_repo is not configured for theme '{$slug}'."];
        }

        $currentVersion = (string) ($meta['version'] ?? '0.0.0');

        $versionService = new VersionService(null, $repo);
        $latest = $versionService->getLatestRelease();
        if ($latest === null) {
            $err = $versionService->getLastError() ?: 'Could not fetch latest theme release.';
            return ['success' => false, 'error' => $err];
        }

        $versionTo = (string) $latest['version'];
        $zipUrl = (string) ($latest['zipball_url'] ?? '');
        if ($zipUrl === '') {
            return ['success' => false, 'error' => 'Latest release does not expose a zipball_url.'];
        }

        $label = 'theme_' . $slug . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $versionTo);
        $tmpDir = rtrim($this->tmpPath, '/') . '/' . $label;
        $this->removeDir($tmpDir);
        @mkdir(dirname($tmpDir), 0755, true);
        @mkdir($tmpDir, 0755, true);

        $zipFile = $tmpDir . '/release.zip';
        if (!$this->downloadZip($zipUrl, $zipFile)) {
            $this->removeDir($tmpDir);
            return ['success' => false, 'error' => 'Failed to download theme release zip.'];
        }

        $extractDir = $tmpDir . '/extract';
        @mkdir($extractDir, 0755, true);
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            $this->removeDir($tmpDir);
            return ['success' => false, 'error' => 'Invalid theme release zip.'];
        }
        $zip->extractTo($extractDir);
        $zip->close();

        $top = array_values(array_filter(scandir($extractDir) ?: [], static function ($d) {
            return $d !== '.' && $d !== '..';
        }));
        $rootDir = count($top) === 1 ? $extractDir . '/' . $top[0] : $extractDir;

        $newThemeDir = $rootDir;
        if (!is_file($newThemeDir . '/style.css') && !is_file($newThemeDir . '/theme.json')) {
            $candidate = $rootDir . '/' . $slug;
            if (is_dir($candidate) && (is_file($candidate . '/style.css') || is_file($candidate . '/theme.json'))) {
                $newThemeDir = $candidate;
            } else {
                $this->removeDir($tmpDir);
                return ['success' => false, 'error' => 'Could not locate theme root in downloaded release.'];
            }
        }

        $backupBase = rtrim($this->backupPath, '/');
        @mkdir($backupBase, 0755, true);
        $backupDir = $backupBase . '/theme_' . $slug . '_' . date('Y-m-d_H-i-s');
        @mkdir($backupDir, 0755, true);
        if (!is_dir($backupDir)) {
            $this->removeDir($tmpDir);
            return ['success' => false, 'error' => 'Failed to create theme backup directory.'];
        }
        $this->copyRecursive($themeDir, $backupDir);

        $this->removeDir($themeDir);
        @mkdir($themeDir, 0755, true);
        $this->copyRecursive($newThemeDir, $themeDir);

        $this->removeDir($tmpDir);
        UpdateLog::log('Theme updated', [
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
            'User-Agent: Modular-CMS-Theme-Update',
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

