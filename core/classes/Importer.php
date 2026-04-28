<?php
namespace Modular;

/**
 * Reads a Modular export bundle (.zip) and imports it into the current install.
 *
 *   $imp = new Modular\Importer($zipPath);
 *   $imp->validate();           // throws on bad bundle
 *   $preview = $imp->preview(); // counts only, no writes
 *   $report  = $imp->run('skip' | 'overwrite' | 'dryrun');
 */
class Importer
{
    private string $zipPath;
    private \ZipArchive $zip;
    private array $manifest = [];
    /** @var array<string, array<int|string, int|string>> table => oldId => newId */
    private array $idMap = [];

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
     * Read-only count of new vs. conflicting rows per table (uses natural keys).
     *
     * @return array<int, array{name: string, rows: int, new: int, conflicts: int, no_dedup?: bool}>
     */
    public function preview(): array
    {
        if (empty($this->manifest)) $this->validate();

        $registry = function_exists('md_export_registry') ? md_export_registry() : [];
        $report   = [];

        foreach ($this->manifest['tables'] as $t) {
            $name = $t['name'];
            $cfg  = $registry[$name] ?? null;
            $rows = $this->readJson('data/' . $name . '.json');
            $entry = [
                'name'      => $name,
                'rows'      => count($rows),
                'new'       => 0,
                'conflicts' => 0,
            ];

            $natural = $cfg['natural_key'] ?? null;
            if (!$natural) {
                $entry['no_dedup'] = true;
                $entry['new']      = count($rows);
                $report[] = $entry;
                continue;
            }
            $table = $cfg['table'];
            foreach ($rows as $row) {
                $where = [];
                foreach ($natural as $col) {
                    $where[$col] = $row[$col] ?? null;
                }
                $existing = $this->findByNaturalKey($table, $where);
                if ($existing !== null) $entry['conflicts']++;
                else                    $entry['new']++;
            }
            $report[] = $entry;
        }
        return $report;
    }

    /**
     * Run the import. $mode is 'skip' (default), 'overwrite', or 'dryrun'.
     * Returns a per-table summary with created/updated/skipped/errors counts.
     */
    public function run(string $mode = 'skip', ?int $importedByUserId = null): array
    {
        if (!in_array($mode, ['skip', 'overwrite', 'dryrun'], true)) {
            throw new \RuntimeException('Invalid mode: ' . $mode);
        }
        if (empty($this->manifest)) $this->validate();

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
                $summary[$name] = $this->importTable($name, $cfg, $rows, $mode, $errors);
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
                                  array &$errors): array
    {
        $table   = $cfg['table'];
        $natural = $cfg['natural_key'] ?? null;
        $fkMap   = $cfg['fk_map']      ?? [];
        $created = $updated = $skipped = 0;
        $deferredSelfFks = []; // [ ['id' => newId, 'sets' => [col => oldVal]] ]
        $this->idMap[$name] = [];

        // Detect self-FKs (same registry references itself) so we can defer them
        $selfFkCols = [];
        foreach ($fkMap as $col => $targetReg) {
            if ($targetReg === $name) $selfFkCols[$col] = true;
        }

        if (!$this->tableExists($table)) {
            $errors[] = "Table '{$table}' does not exist in destination — skipping {$name}";
            return ['created' => 0, 'updated' => 0, 'skipped' => count($rows), 'missing_table' => true];
        }

        foreach ($rows as $row) {
            try {
                $exportId = $row['_export_id'] ?? null;
                unset($row['_export_id']);
                if (array_key_exists('id', $row)) unset($row['id']); // let DB assign

                // Resolve FKs (defer self-references; skip row if a non-null FK can't be remapped)
                $deferred       = [];
                $fkUnresolvable = false;
                foreach ($fkMap as $col => $target) {
                    if (!array_key_exists($col, $row)) continue;
                    $oldVal = $row[$col];
                    if ($oldVal === null || $oldVal === '') continue;
                    if (isset($selfFkCols[$col])) {
                        $deferred[$col] = $oldVal;
                        $row[$col]      = null;
                        continue;
                    }
                    $newVal = $this->idMap[$target][$oldVal] ?? null;
                    if ($newVal === null) {
                        // Source had a value but the referenced row didn't make it
                        // into the destination — orphan. Skip without raising an error.
                        $fkUnresolvable = true;
                        break;
                    }
                    $row[$col] = $newVal;
                }
                if ($fkUnresolvable) {
                    $skipped++;
                    continue;
                }

                // Filter only columns the destination table actually has
                $row = $this->filterRowToTable($table, $row);

                // Find existing by natural key
                $existingId = null;
                if ($natural) {
                    $where = [];
                    foreach ($natural as $col) {
                        $where[$col] = $row[$col] ?? null;
                    }
                    $existingId = $this->findByNaturalKey($table, $where);
                }

                if ($existingId !== null) {
                    if ($mode === 'overwrite') {
                        md_db_update($table, $row, ['id' => $existingId]);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                    $newId = $existingId;
                } else {
                    $newId = md_db_insert($table, $row);
                    if ($newId === false) {
                        $errors[] = "Insert failed in {$table} for export_id={$exportId}";
                        continue;
                    }
                    $created++;
                }

                if ($exportId !== null) {
                    $this->idMap[$name][$exportId] = $newId;
                }
                if (!empty($deferred)) {
                    $deferredSelfFks[] = ['id' => $newId, 'sets' => $deferred];
                }
            } catch (\Throwable $e) {
                $errors[] = "{$name}: " . $e->getMessage();
            }
        }

        // 2nd pass: resolve self-FKs now that all rows of this table have IDs
        foreach ($deferredSelfFks as $rec) {
            $update = [];
            foreach ($rec['sets'] as $col => $oldVal) {
                $newVal = $this->idMap[$name][$oldVal] ?? null;
                if ($newVal !== null) $update[$col] = $newVal;
            }
            if ($update) {
                md_db_update($table, $update, ['id' => $rec['id']]);
            }
        }

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
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
