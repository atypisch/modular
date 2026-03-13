<?php
/**
 * Version service – read local version, fetch latest from GitHub API, compare semver
 */

namespace Modular\Update;

class VersionService {
    private string $versionFile;
    private string $buildFile;
    private string $githubRepo;
    private string $apiBase;
    private ?string $lastError = null;

    public function __construct(?string $versionFile = null, ?string $githubRepo = null, ?string $apiBase = null) {
        $this->versionFile = $versionFile ?? (defined('CORE_PATH') ? CORE_PATH . '/version.json' : __DIR__ . '/../../version.json');
        // build.json lives in /core/build/
        $this->buildFile = defined('CORE_PATH')
            ? CORE_PATH . '/build/build.json'
            : __DIR__ . '/../../build/build.json';
        $this->githubRepo = $githubRepo ?? (defined('UPDATE_CORE_GITHUB_REPO') ? UPDATE_CORE_GITHUB_REPO : '');
        $this->apiBase = $apiBase ?? (defined('UPDATE_GITHUB_API_BASE') ? UPDATE_GITHUB_API_BASE : 'https://api.github.com');
    }

    /**
     * Get current installed CMS version.
     * Prefers build/build.json (single source of truth); falls back to core/version.json.
     */
    public function getCurrentVersion(): array {
        $default = ['version' => '0.0.0', 'name' => 'Modular CMS', 'description' => ''];

        // Prefer build.json
        if (is_readable($this->buildFile)) {
            $json = @file_get_contents($this->buildFile);
            $data = $json !== false ? @json_decode($json, true) : null;
            if (is_array($data) && !empty($data['version'])) {
                return [
                    'version'     => $data['version'],
                    'name'        => 'Modular CMS',
                    'description' => '',
                    'build_date'  => $data['build_date'] ?? '',
                    'core'        => $data['core'] ?? $data['version'],
                ];
            }
        }

        // Fall back to core/version.json
        if (!is_readable($this->versionFile)) {
            return $default;
        }
        $json = @file_get_contents($this->versionFile);
        if ($json === false) {
            return $default;
        }
        $data = @json_decode($json, true);
        if (!is_array($data) || empty($data['version'])) {
            return $default;
        }
        return [
            'version'     => $data['version'],
            'name'        => $data['name'] ?? $default['name'],
            'description' => $data['description'] ?? $default['description'],
        ];
    }

    /**
     * Read build/build.json directly. Returns null if not present or invalid.
     */
    public function getBuildInfo(): ?array {
        if (!is_readable($this->buildFile)) {
            return null;
        }
        $json = @file_get_contents($this->buildFile);
        if ($json === false) {
            return null;
        }
        $data = @json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Update build/build.json with new version information after a successful update.
     */
    public function updateBuildInfo(array $updates): bool {
        $current = $this->getBuildInfo() ?? [];
        $merged  = array_merge($current, $updates);
        $merged['build_date'] = date('Y-m-d');
        return (bool) @file_put_contents(
            $this->buildFile,
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    /**
     * Fetch latest release from GitHub API (owner/repo)
     */
    public function getLatestRelease(): ?array {
        $this->lastError = null;
        if ($this->githubRepo === '') {
            $this->lastError = 'No UPDATE_CORE_GITHUB_REPO configured.';
            $this->logError('version.latest.core.repo_missing', ['repo' => $this->githubRepo]);
            return null;
        }
        return $this->fetchLatestReleaseForRepo($this->githubRepo, 'core');
    }

    /**
     * Fetch latest release for an arbitrary repo (owner/repo).
     */
    public function getLatestReleaseForRepo(string $repo, string $context = 'generic'): ?array {
        $this->lastError = null;
        if ($repo === '') {
            $this->lastError = 'GitHub repository not configured.';
            $this->logError('version.latest.repo_missing', ['context' => $context]);
            return null;
        }
        return $this->fetchLatestReleaseForRepo($repo, $context);
    }

    /**
     * Last error message from getLatestRelease*() or null if none.
     */
    public function getLastError(): ?string {
        return $this->lastError;
    }

    private function fetchLatestReleaseForRepo(string $repo, string $context): ?array {
        $url = rtrim($this->apiBase, '/') . '/repos/' . $repo . '/releases/latest';

        $headers = [
            'User-Agent: Modular-CMS-Update-Check',
            'Accept: application/vnd.github.v3+json',
        ];
        if (defined('UPDATE_GITHUB_TOKEN') && UPDATE_GITHUB_TOKEN !== '') {
            $headers[] = 'Authorization: Bearer ' . UPDATE_GITHUB_TOKEN;
        }

        $status = 0;
        $body = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $body = curl_exec($ch);
            if ($body === false) {
                $this->lastError = 'cURL error: ' . curl_error($ch);
            }
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers) . "\r\n",
                    'timeout' => 10,
                ],
            ]);
            $body = @file_get_contents($url, false, $ctx);
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $line) {
                    if (preg_match('/^HTTP\\/\\d+\\.\\d+\\s+(\\d+)/', $line, $m)) {
                        $status = (int) $m[1];
                        break;
                    }
                }
            }
            if ($body === false && $this->lastError === null) {
                $this->lastError = 'Network error while contacting GitHub.';
            }
        }

        if ($status !== 200 || $body === null || $body === false) {
            if ($status === 404) {
                $this->lastError = $this->lastError ?? 'No releases found for this repository.';
            } elseif ($status === 403) {
                $this->lastError = $this->lastError ?? 'Forbidden or rate limited by GitHub. Configure UPDATE_GITHUB_TOKEN.';
            } elseif ($status === 0) {
                $this->lastError = $this->lastError ?? 'Network error while contacting GitHub.';
            } else {
                $this->lastError = $this->lastError ?? ('GitHub API returned HTTP ' . $status . '.');
            }
            $this->logError('version.latest.fetch_failed', [
                'context' => $context,
                'repo' => $repo,
                'status' => $status,
                'error' => $this->lastError,
            ]);
            return null;
        }

        $data = @json_decode($body, true);
        if (!is_array($data) || empty($data['tag_name'])) {
            $this->lastError = 'Malformed response from GitHub API.';
            $this->logError('version.latest.malformed', ['context' => $context, 'repo' => $repo]);
            return null;
        }

        $tag = $data['tag_name'];
        $version = ltrim($tag, 'v');
        return [
            'version' => $version,
            'tag_name' => $tag,
            'name' => $data['name'] ?? $tag,
            'body' => $data['body'] ?? '',
            'published_at' => $data['published_at'] ?? null,
            'html_url' => $data['html_url'] ?? '',
            'zipball_url' => $data['zipball_url'] ?? '',
            'tarball_url' => $data['tarball_url'] ?? '',
        ];
    }

    private function logError(string $message, array $context = []): void {
        $fqcn = __NAMESPACE__ . '\\UpdateLog';
        if (class_exists($fqcn)) {
            /** @var class-string $fqcn */
            $fqcn::log($message, $context);
        } else {
            error_log($message . ' ' . json_encode($context));
        }
    }

    /**
     * Compare two semver strings. Returns: -1 if a < b, 0 if a == b, 1 if a > b
     */
    public static function compareSemver(string $a, string $b): int {
        $va = self::parseSemver($a);
        $vb = self::parseSemver($b);
        foreach (['major', 'minor', 'patch'] as $k) {
            if ($va[$k] < $vb[$k]) return -1;
            if ($va[$k] > $vb[$k]) return 1;
        }
        return 0;
    }

    /**
     * Parse semver string into [major, minor, patch]
     */
    public static function parseSemver(string $v): array {
        $v = preg_replace('/^v/', '', trim($v));
        $parts = explode('.', $v);
        return [
            'major' => (int) ($parts[0] ?? 0),
            'minor' => (int) ($parts[1] ?? 0),
            'patch' => (int) (preg_replace('/[^0-9].*$/', '', $parts[2] ?? '0')),
        ];
    }

    /**
     * Whether an update is available (latest > current)
     */
    public function isUpdateAvailable(): bool {
        $current = $this->getCurrentVersion();
        $latest = $this->getLatestRelease();
        if ($latest === null) {
            return false;
        }
        return self::compareSemver($current['version'], $latest['version']) < 0;
    }
}
