<?php
namespace Modular;

/**
 * Reads a Modular export bundle (.zip) and imports it into the current install.
 *
 *   $imp = new Modular\Importer($zipPath);
 *   $imp->validate();           // throws on bad bundle
 *   $preview = Importer::drySummaryToPreviewArray($manifest,
 *       $imp->run('dryrun', null, 'skip')); // FK-aware counts (same logic as run)
 *   $report  = $imp->run('skip', userId); // or 'overwrite', 'dryrun', plus duplicatePolicy for dryrun
 */
class Importer
{
    private string $zipPath;
    private \ZipArchive $zip;
    private array $manifest = [];
    /** @var array<string, array<int|string, int|string>> table => oldId => newId */
    private array $idMap = [];
    /** User id to stamp on NOT NULL owner columns when a bundle omits them. */
    private ?int $importUserId = null;
    /** @var array<string, int|null> email => local user id (resolved lazily) */
    private array $userIdByEmailCache = [];

    public function __construct(string $zipPath)
    {
        if (!is_file($zipPath)) {
            throw new \RuntimeException("Bundle not found: {$zipPath}");
        }
        $this->zipPath = $zipPath;
        $this->zip     = new \ZipArchive();
        if ($this->zip->open($zipPath) !== true) {
            throw new \RuntimeException("Could not open bundle: {$zipPath}");
        }
    }

    public function __destruct()
    {
        @$this->zip->close();
    }

    /**
     * Validate manifest + that every listed data file is present in the zip.
     * @throws \RuntimeException
     */
    public function validate(): array
    {
        $raw = $this->zip->getFromName('manifest.json');
        if ($raw === false) {
            throw new \RuntimeException('Bundle is missing manifest.json');
        }
        $m = json_decode($raw, true);
        if (!is_array($m) || empty($m['version']) || empty($m['tables'])) {
            throw new \RuntimeException('manifest.json is malformed');
        }
        if ($m['version'] !== '1.0') {
            throw new \RuntimeException('Unsupported bundle version: ' . $m['version']);
        }
        foreach ($m['tables'] as $t) {
            $name = $t['name'] ?? '';
            if ($name === '' || $this->zip->locateName('data/' . $name . '.json') === false) {
                throw new \RuntimeException("Bundle references data/{$name}.json but it is missing");
            }
        }
        $this->manifest = $m;
        return $m;
    }

    /**
     * Turn a dry-run import summary into the same shape as the legacy preview panel.
     * Uses manifest row counts and per-table registry metadata for insert-only tables.
     *
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $drySummary return value of run('dryrun', ...)
     * @return array<int, array{name: string, rows: int, new: int, conflicts: int, no_dedup?: bool, skipped_fk?: int}>
     */
    public static function drySummaryToPreviewArray(array $manifest, array $drySummary): array
    {
        $registry = function_exists('md_export_registry') ? md_export_registry() : [];
        $report   = [];

        foreach ($manifest['tables'] ?? [] as $t) {
            $name = $t['name'] ?? '';
            if ($name === '') {
                continue;
            }
            $bundleRows = (int) ($t['rows'] ?? 0);
            $cfg          = $registry[$name] ?? null;
            if ($cfg === null) {
                $natural = false;
            } else {
                $natural = $cfg['natural_key'] ?? null;
            }

            $dr = $drySummary[$name] ?? null;
            if (!is_array($dr)) {
                $report[] = [
                    'name'      => $name,
                    'rows'      => $bundleRows,
                    'new'       => 0,
                    'conflicts' => 0,
                ];
                continue;
            }

            $created    = (int) ($dr['created'] ?? 0);
            $updated    = (int) ($dr['updated'] ?? 0);
            $skippedFk  = (int) ($dr['skipped_fk'] ?? 0);
            $skippedDup = (int) ($dr['skipped_duplicate'] ?? 0);
            if (!isset($dr['skipped_fk']) && !isset($dr['skipped_duplicate'])) {
                $skippedDup = (int) ($dr['skipped'] ?? 0);
            }

            $entry = [
                'name'      => $name,
                'rows'      => $bundleRows,
                'new'       => $created,
                'conflicts' => $skippedDup + $updated,
            ];
            if ($natural === false) {
                // bundle table with no md_export_register() on this install — keep dry-run counts as-is
            } elseif (!$natural) {
                $entry['no_dedup'] = true;
                $entry['new']       = $created;
                $entry['conflicts'] = 0;
            }
            if ($skippedFk > 0) {
                $entry['skipped_fk'] = $skippedFk;
            }
            $report[] = $entry;
        }

        return $report;
    }

    /**
     * Run the import. $mode is 'skip' (default), 'overwrite', or 'dryrun'.
     * When $mode is 'dryrun', $duplicatePolicy selects skip vs overwrite for rows that match a natural key.
     * Returns a per-table summary with created/updated/skipped/errors counts.
     */
    public function run(string $mode = 'skip', ?int $importedByUserId = null, string $duplicatePolicy = 'skip'): array
    {
        if (!in_array($mode, ['skip', 'overwrite', 'dryrun'], true)) {
            throw new \RuntimeException('Invalid mode: ' . $mode);
        }
        if (empty($this->manifest)) $this->validate();
        $this->importUserId = $importedByUserId;

        @$this->zip->close();
        if ($this->zip->open($this->zipPath) !== true) {
            throw new \RuntimeException('Could not reopen bundle: ' . $this->zipPath);
        }

        $registry = function_exists('md_export_registry') ? md_export_registry() : [];
        $db       = md_db();
        $summary  = [];
        $errors   = [];
        $this->idMap = [];

        $db->beginTransaction();
        try {
            foreach ($registry as $name => $cfg) {
                if (!$this->bundleHasTable($name)) continue; // table not in this export
                $rows = $this->readJson('data/' . $name . '.json');
                $summary[$name] = $this->importTable($name, $cfg, $rows, $mode, $errors, $duplicatePolicy);
            }

            // Media files
            $mediaSummary = $this->importMedia($mode);
            if ($mediaSummary !== null) {
                $summary['_media'] = $mediaSummary;
            }

            if ($mode === 'dryrun') {
                $db->rollBack();
            } else {
                $db->commit();
            }
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors[] = $e->getMessage();
            $summary['_aborted'] = true;
        }

        $summary['_errors'] = $errors;
        $summary['_mode']   = $mode;

        // Audit log entry (only for real runs)
        if ($mode !== 'dryrun' && empty($summary['_aborted'])) {
            $this->logImport($mode, $summary, $importedByUserId);
        }

        return $summary;
    }

    private function importTable(string $name, array $cfg, array $rows, string $mode,
                                  array &$errors, string $duplicatePolicy = 'skip'): array
    {
        $table   = $cfg['table'];
        $natural = $cfg['natural_key'] ?? null;
        $fkMap   = $cfg['fk_map']      ?? [];
        $created = $updated = $skippedDup = $skippedFk = 0;
        $deferredSelfFks = [];
        $this->idMap[$name] = [];

        // Post-type bundles ship no `users` table — author/owner ids can't be
        // remapped. Relink those columns by email (carried on the row) or to the
        // importing user, and drop them from the FK map so they aren't skipped.
        $emailKeyFor = ['author_id' => '_author_email', 'created_by' => '_created_by_email', 'updated_by' => '_updated_by_email'];
        $userCols = [];
        if (!$this->bundleHasTable('users')) {
            foreach ($fkMap as $col => $target) {
                if ($target === 'users') { $userCols[] = $col; unset($fkMap[$col]); }
            }
        }

        $effectiveDup = $mode === 'overwrite'
            ? 'overwrite'
            : (($mode === 'dryrun' && $duplicatePolicy === 'overwrite') ? 'overwrite' : 'skip');

        $selfFkCols = [];
        foreach ($fkMap as $col => $targetReg) {
            if ($targetReg === $name) $selfFkCols[$col] = true;
        }

        if (!$this->tableExists($table)) {
            $errors[] = "Table '{$table}' does not exist in destination — skipping {$name}";
            return [
                'created' => 0, 'updated' => 0, 'skipped' => count($rows),
                'skipped_duplicate' => 0, 'skipped_fk' => 0, 'missing_table' => true,
            ];
        }

        $tableCols = $this->tableColumns($table);

        foreach ($rows as $row) {
            try {
                $exportId = $row['_export_id'] ?? null;
                unset($row['_export_id']);
                if (array_key_exists('id', $row)) unset($row['id']);

                // Relink user-owned columns from the carried email, else the importer.
                foreach ($userCols as $col) {
                    if (array_key_exists($col, $tableCols)) {
                        $emailKey = $emailKeyFor[$col] ?? null;
                        $uid = ($emailKey !== null && !empty($row[$emailKey])) ? $this->userIdByEmail((string) $row[$emailKey]) : null;
                        $row[$col] = $uid ?: ($this->importUserId ?: 1);
                    }
                }
                unset($row['_author_email'], $row['_created_by_email'], $row['_updated_by_email']);

                $deferred       = [];
                $fkUnresolvable = false;
                foreach ($fkMap as $col => $target) {
                    if (!array_key_exists($col, $row)) continue;
                    $oldVal = $row[$col];
                    if ($oldVal === null || $oldVal === '' || $oldVal === 0 || $oldVal === '0') continue;
                    if (isset($selfFkCols[$col])) {
                        $deferred[$col] = $oldVal;
                        $row[$col]      = null;
                        continue;
                    }
                    $newVal = $this->idMapLookup($target, $oldVal);
                    if ($newVal === null) {
                        $fkUnresolvable = true;
                        break;
                    }
                    $row[$col] = $newVal;
                }
                if ($fkUnresolvable) {
                    $skippedFk++;
                    continue;
                }

                $row = $this->filterRowToTable($table, $row);
                $row = $this->normalizeRowForDb($table, $row);

                // Stamp NOT NULL owner columns the bundle omitted (e.g. a structure
                // export that intentionally drops created_by/updated_by to stay
                // user-agnostic). Attribute to the importing CMS user so brand-new
                // rows insert cleanly instead of hitting "doesn't have a default".
                // $tableCols is a column-name => index map (array_flip), so the
                // column names are its KEYS.
                $ownerFallback = $this->importUserId ?: 1;
                foreach (['author_id', 'created_by', 'updated_by'] as $ownerCol) {
                    if (array_key_exists($ownerCol, $tableCols)
                        && (!array_key_exists($ownerCol, $row) || $row[$ownerCol] === null || $row[$ownerCol] === '')) {
                        $row[$ownerCol] = $ownerFallback;
                    }
                }

                $existingId = null;
                if ($natural) {
                    $where = [];
                    foreach ($natural as $col) {
                        $where[$col] = $row[$col] ?? null;
                    }
                    $existingId = $this->findByNaturalKey($table, $where);
                }

                if ($existingId !== null) {
                    if ($effectiveDup === 'overwrite') {
                        md_db_update($table, $row, ['id' => $existingId]);
                        $updated++;
                    } else {
                        $skippedDup++;
                    }
                    $newId = $existingId;
                } else {
                    $newId = md_db_insert($table, $row);
                    if ($newId === false) {
                        $detail = function_exists('md_db_last_error') ? md_db_last_error() : null;
                        $errors[] = "Insert failed in {$table} for export_id={$exportId}"
                            . (($detail !== null && $detail !== '') ? ": {$detail}" : '');
                        continue;
                    }
                    $created++;
                }

                if ($exportId !== null) {
                    $this->rememberIdMap($name, $exportId, $newId);
                }
                if (!empty($deferred)) {
                    $deferredSelfFks[] = ['id' => $newId, 'sets' => $deferred];
                }
            } catch (\Throwable $e) {
                $errors[] = "{$name}: " . $e->getMessage();
            }
        }

        foreach ($deferredSelfFks as $rec) {
            $update = [];
            foreach ($rec['sets'] as $col => $oldVal) {
                $newVal = $this->idMapLookup($name, $oldVal);
                if ($newVal !== null) $update[$col] = $newVal;
            }
            if ($update) {
                md_db_update($table, $update, ['id' => $rec['id']]);
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skippedDup + $skippedFk,
            'skipped_duplicate' => $skippedDup,
            'skipped_fk' => $skippedFk,
        ];
    }

    /**
     * JSON columns must receive SQL strings; bundled nested JSON becomes PHP arrays after json_decode.
     */
    private function normalizeRowForDb(string $table, array $row): array
    {
        foreach ($row as $k => $v) {
            if (is_array($v)) {
                $row[$k] = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
        if ($table === 'post_types') {
            if (!isset($row['supports']) || $row['supports'] === null || $row['supports'] === '') {
                $row['supports'] = '["title","editor"]';
            }
            if (!isset($row['mode']) || $row['mode'] === null || $row['mode'] === '') {
                $row['mode'] = 'fixed';
            }
        }
        return $row;
    }

    private function idMapLookup(string $registryName, $oldPk): ?int
    {
        $map = $this->idMap[$registryName] ?? [];
        if ($map === [] || $oldPk === null || $oldPk === '') {
            return null;
        }
        if (array_key_exists($oldPk, $map)) {
            return (int) $map[$oldPk];
        }
        if (is_string($oldPk) && ctype_digit($oldPk)) {
            $ik = (int) $oldPk;
            if (array_key_exists($ik, $map)) {
                return (int) $map[$ik];
            }
        }
        if (is_int($oldPk)) {
            $sk = (string) $oldPk;
            if (array_key_exists($sk, $map)) {
                return (int) $map[$sk];
            }
        }
        return null;
    }

    private function rememberIdMap(string $name, $exportId, $newId): void
    {
        $this->idMap[$name][$exportId] = $newId;
        if (is_string($exportId) && ctype_digit((string) $exportId)) {
            $this->idMap[$name][(int) $exportId] = $newId;
        } elseif (is_int($exportId)) {
            $this->idMap[$name][(string) $exportId] = $newId;
        }
    }

    private function importMedia(string $mode): ?array
    {
        if (empty($this->manifest['include_media'])) return null;
        if (!defined('UPLOADS_PATH')) return ['copied' => 0, 'skipped' => 0, 'note' => 'UPLOADS_PATH undefined'];

        $destBase = rtrim(UPLOADS_PATH, '/') . '/media';
        if ($mode !== 'dryrun' && !is_dir($destBase)) {
            @mkdir($destBase, 0755, true);
        }

        $copied = 0; $skipped = 0;
        $prefix = 'uploads/media/';
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $stat = $this->zip->statIndex($i);
            $entry = $stat['name'];
            if (strpos($entry, $prefix) !== 0) continue;
            $rel  = substr($entry, strlen($prefix));
            if ($rel === '' || substr($rel, -1) === '/') continue;
            $dest = $destBase . '/' . $rel;
            if (file_exists($dest) && $mode !== 'overwrite') {
                $skipped++;
                continue;
            }
            if ($mode === 'dryrun') {
                $copied++;
                continue;
            }
            $destDir = dirname($dest);
            if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
            $stream = $this->zip->getStream($entry);
            if (!$stream) { $skipped++; continue; }
            $out = @fopen($dest, 'wb');
            if (!$out) { fclose($stream); $skipped++; continue; }
            stream_copy_to_stream($stream, $out);
            fclose($stream);
            fclose($out);
            $copied++;
        }
        return ['copied' => $copied, 'skipped' => $skipped];
    }

    private function findByNaturalKey(string $table, array $where): ?int
    {
        $cond  = [];
        $vals  = [];
        foreach ($where as $col => $val) {
            if ($val === null) {
                $cond[] = "`{$col}` IS NULL";
            } else {
                $cond[] = "`{$col}` = ?";
                $vals[] = $val;
            }
        }
        $hasId = $this->columnExists($table, 'id');
        $select = $hasId ? '`id`' : '1';
        $sql = "SELECT {$select} FROM `{$table}` WHERE " . implode(' AND ', $cond) . ' LIMIT 1';
        try {
            $row = md_db_row($sql, $vals);
            if (!$row) return null;
            return $hasId ? (int) $row['id'] : 0; // 0 means "exists but no id"
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Resolve an email to an existing local user id, or null if none matches. */
    private function userIdByEmail(string $email): ?int
    {
        if (array_key_exists($email, $this->userIdByEmailCache)) {
            return $this->userIdByEmailCache[$email];
        }
        $id = null;
        try {
            $row = md_db_row('SELECT id FROM `users` WHERE email = ? LIMIT 1', [$email]);
            if ($row) $id = (int) $row['id'];
        } catch (\Throwable $e) {
            $id = null;
        }
        return $this->userIdByEmailCache[$email] = $id;
    }

    private function readJson(string $entry): array
    {
        $raw = $this->zip->getFromName($entry);
        if ($raw === false) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function bundleHasTable(string $name): bool
    {
        foreach ($this->manifest['tables'] as $t) {
            if (($t['name'] ?? '') === $name) return true;
        }
        return false;
    }

    private array $tableColumnsCache = [];
    private function tableColumns(string $table): array
    {
        if (isset($this->tableColumnsCache[$table])) return $this->tableColumnsCache[$table];
        try {
            $rows = md_db_query('SHOW COLUMNS FROM `' . $table . '`');
            $cols = array_map(fn($r) => $r['Field'] ?? '', $rows);
        } catch (\Throwable $e) {
            $cols = [];
        }
        return $this->tableColumnsCache[$table] = array_flip($cols);
    }

    private function columnExists(string $table, string $col): bool
    {
        return isset($this->tableColumns($table)[$col]);
    }

    private function filterRowToTable(string $table, array $row): array
    {
        $cols = $this->tableColumns($table);
        return array_intersect_key($row, $cols);
    }

    private function tableExists(string $table): bool
    {
        try {
            $safe = str_replace('`', '', $table);
            $r = md_db_var('SHOW TABLES LIKE ' . md_db()->quote($safe));
            return (bool) $r;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function logImport(string $mode, array $summary, ?int $userId): void
    {
        try {
            md_db_insert('import_log', [
                'imported_by'     => $userId ?: 0,
                'source_url'      => $this->manifest['source_url']     ?? null,
                'bundle_filename' => basename($this->zipPath),
                'mode'            => $mode,
                'summary'         => json_encode($summary, JSON_UNESCAPED_UNICODE),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('import_log insert failed: ' . $e->getMessage());
        }
    }
}
