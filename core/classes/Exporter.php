<?php
namespace Modular;

/**
 * Builds a portable .zip bundle of all registered tables (and optionally
 * the public/uploads/media/ tree) so it can be imported into another
 * Modular install via Modular\Importer.
 */
class Exporter
{
    /** @var array<string, array> Registry from md_export_registry() */
    private array $registry;

    /** @var bool */
    private bool $includeMedia;

    /** @var string|null e.g. post_type */
    private ?string $profile;

    /** @var string|null Post type slug when profile is post_type */
    private ?string $postTypeSlug;

    /** @var string Absolute path to write the .zip to */
    private string $bundlePath;

    public function __construct(
        bool $includeMedia = false,
        ?string $bundlePath = null,
        ?string $profile = null,
        ?string $postTypeSlug = null
    )
    {
        if (!function_exists('md_export_registry')) {
            throw new \RuntimeException('Export registry not loaded — include core/functions/export.php first.');
        }
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('PHP ZipArchive extension is required.');
        }
        $this->registry     = md_export_registry();
        $this->includeMedia = $includeMedia;
        $this->profile      = $profile;
        $this->postTypeSlug = $postTypeSlug;

        if ($bundlePath === null) {
            $host       = $_SERVER['HTTP_HOST'] ?? 'modular';
            $hostSlug   = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($host));
            $stamp      = date('Ymd-His');
            $tmpDir     = sys_get_temp_dir();
            $bundlePath = $tmpDir . '/modular-export-' . $hostSlug . '-' . $stamp . '.zip';
        }
        $this->bundlePath = $bundlePath;
    }

    /**
     * Build the bundle. Returns absolute path to the created .zip.
     *
     * @return string
     * @throws \RuntimeException on failure
     */
    public function build(): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->bundlePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create zip at ' . $this->bundlePath);
        }

        $tables = [];
        foreach ($this->registry as $name => $cfg) {
            $rows = $this->dumpTable($cfg);
            if ($rows === null) {
                continue; // table missing — silently skip
            }
            $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $zip->addFromString('data/' . $name . '.json', $json);

            $entry = [
                'name'        => $name,
                'table'       => $cfg['table'],
                'rows'        => count($rows),
                'natural_key' => $cfg['natural_key'],
            ];
            if (!empty($cfg['module'])) {
                $entry['module'] = $cfg['module'];
            }
            $tables[] = $entry;
        }

        $manifest = [
            'version'         => '1.0',
            'source_url'      => $this->sourceUrl(),
            'exported_at'     => gmdate('c'),
            'modular_version' => $this->modularVersion(),
            'include_media'   => $this->includeMedia,
            'tables'          => $tables,
        ];
        if ($this->profile !== null && $this->profile !== '') {
            $manifest['profile'] = $this->profile;
        }
        if ($this->profile === 'post_type' && $this->postTypeSlug !== null && $this->postTypeSlug !== '') {
            $manifest['post_type'] = $this->postTypeSlug;
        }
        $zip->addFromString('manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($this->includeMedia) {
            $this->addMediaFiles($zip);
        }

        $zip->close();
        return $this->bundlePath;
    }

    /**
     * Stream the built bundle to the client and exit.
     */
    public function streamDownload(): void
    {
        $path = $this->build();
        $name = basename($path);
        $size = filesize($path);

        if (!headers_sent()) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $name . '"');
            header('Content-Length: ' . $size);
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }
        readfile($path);
        @unlink($path);
        exit;
    }

    /**
     * Dump a single table to an array of rows (with _export_id where applicable).
     * Returns null if the table does not exist (so it can be silently skipped).
     */
    private function dumpTable(array $cfg): ?array
    {
        $table = $cfg['table'];
        $where = $cfg['where'] ?? null;

        try {
            $safe   = str_replace('`', '', $table);
            $exists = md_db_var('SHOW TABLES LIKE ' . md_db()->quote($safe));
            if (!$exists) {
                return null;
            }

            $sql  = 'SELECT * FROM `' . $safe . '`' . ($where ? ' WHERE ' . $where : '');
            $rows = md_db_query($sql);
        } catch (\Throwable $e) {
            error_log('Exporter dumpTable error on ' . $table . ': ' . $e->getMessage());
            return null;
        }

        $skip = array_flip($cfg['skip_columns'] ?? []);
        $hasIdColumn = false;
        if (!empty($rows)) {
            $hasIdColumn = array_key_exists('id', $rows[0]);
        }

        foreach ($rows as &$row) {
            if ($hasIdColumn) {
                $row['_export_id'] = $row['id'];
            }
            if (!empty($skip)) {
                $row = array_diff_key($row, $skip);
            }
        }
        unset($row);

        // Post-type bundles carry author identity as email (not a users table),
        // so posts relink to existing users on import. See Importer::importTable().
        if ($this->profile === 'post_type' && $table === 'posts' && !empty($rows)) {
            $this->attachAuthorEmails($rows);
        }

        return $rows;
    }

    /**
     * Replace author/creator user-id references with emails on exported posts.
     * Adds _author_email / _created_by_email / _updated_by_email per row.
     */
    private function attachAuthorEmails(array &$rows): void
    {
        $ids = [];
        foreach ($rows as $row) {
            foreach (['author_id', 'created_by', 'updated_by'] as $col) {
                if (!empty($row[$col])) { $ids[(int) $row[$col]] = true; }
            }
        }
        if (!$ids) { return; }

        $ids   = array_keys($ids);
        $place = implode(',', array_fill(0, count($ids), '?'));
        try {
            $userRows = md_db_query('SELECT id, email FROM `users` WHERE id IN (' . $place . ')', $ids);
        } catch (\Throwable $e) {
            error_log('Exporter attachAuthorEmails error: ' . $e->getMessage());
            return;
        }
        $emailById = [];
        foreach ($userRows as $u) { $emailById[(int) $u['id']] = $u['email']; }

        foreach ($rows as &$row) {
            foreach (['author_id' => '_author_email', 'created_by' => '_created_by_email', 'updated_by' => '_updated_by_email'] as $col => $key) {
                if (!empty($row[$col]) && isset($emailById[(int) $row[$col]])) {
                    $row[$key] = $emailById[(int) $row[$col]];
                }
            }
        }
        unset($row);
    }

    /**
     * Recursively add public/uploads/media/ to uploads/media/ inside the zip.
     */
    private function addMediaFiles(\ZipArchive $zip): void
    {
        if (!defined('UPLOADS_PATH')) {
            return;
        }
        if ($this->profile === 'post_type') {
            $this->addMediaFilesFromRegistry($zip);
            return;
        }
        $mediaDir = rtrim(UPLOADS_PATH, '/') . '/media';
        if (!is_dir($mediaDir)) {
            return;
        }
        $base = realpath($mediaDir);
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iter as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) continue;
            $abs = $file->getRealPath();
            $rel = ltrim(str_replace($base, '', $abs), '/');
            $zip->addFile($abs, 'uploads/media/' . $rel);
        }
    }

    private function addMediaFilesFromRegistry(\ZipArchive $zip): void
    {
        $cfg = $this->registry['media'] ?? null;
        if ($cfg === null) {
            return;
        }
        $rows = $this->dumpTable($cfg);
        if ($rows === null || $rows === []) {
            return;
        }
        $mediaDir = rtrim(UPLOADS_PATH, '/') . '/media';
        $base = is_dir($mediaDir) ? realpath($mediaDir) : null;
        foreach ($rows as $row) {
            $filePath = trim((string) ($row['file_path'] ?? ''));
            if ($filePath === '') {
                continue;
            }
            $abs = null;
            if ($base !== null && strpos($filePath, '/') !== 0) {
                $candidate = $base . '/' . ltrim($filePath, '/');
                if (is_file($candidate)) {
                    $abs = realpath($candidate);
                }
            }
            if ($abs === null && is_file($filePath)) {
                $abs = realpath($filePath);
            }
            if ($abs === null || $base === null) {
                continue;
            }
            $rel = ltrim(str_replace($base, '', $abs), '/');
            if ($rel === '' || strpos($rel, '..') !== false) {
                continue;
            }
            $zip->addFile($abs, 'uploads/media/' . $rel);
        }
    }

    private function sourceUrl(): string
    {
        if (defined('SITE_URL')) return rtrim(SITE_URL, '/');
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $proto . '://' . $host;
    }

    private function modularVersion(): string
    {
        $composer = (defined('MODULAR_PATH') ? MODULAR_PATH : dirname(__DIR__, 2)) . '/composer.json';
        if (is_file($composer)) {
            $data = json_decode((string) file_get_contents($composer), true);
            if (!empty($data['version'])) return (string) $data['version'];
        }
        return 'unknown';
    }
}
