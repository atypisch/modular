<?php
/**
 * Settings API Controller
 * Read/write the settings table (key/value), plus theme-token persistence
 * (the Atelier design system) under the `theme_tokens` option.
 */

namespace Modular\API;

class SettingsController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /** GET /settings — all options as a flat map (JSON values decoded). */
    public function index() {
        $stmt = $this->db->query("SELECT option_name, option_value FROM settings");
        $out = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $out[$row['option_name']] = $this->maybeJson($row['option_value']);
        }
        return $out;
    }

    /** PUT /settings — body { options: { name: value, ... } }; upsert each. */
    public function update() {
        $in = $this->body();
        $options = $in['options'] ?? $in; // accept either {options:{}} or a flat map
        if (!is_array($options)) return ['error' => 'options must be an object', 'code' => 400];
        $n = 0;
        foreach ($options as $name => $value) {
            $this->set((string)$name, $value);
            $n++;
        }
        return ['message' => 'Settings saved', 'count' => $n];
    }

    /** GET /settings/theme — the persisted Atelier token set (or {}). */
    public function getTheme() {
        $stmt = $this->db->prepare("SELECT option_value FROM settings WHERE option_name = 'theme_tokens' LIMIT 1");
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return ['tokens' => $val ? (json_decode($val, true) ?: (object)[]) : (object)[]];
    }

    /** PUT /settings/theme — body = token map; persisted as JSON. */
    public function setTheme() {
        $in = $this->body();
        $tokens = $in['tokens'] ?? $in;
        $this->set('theme_tokens', $tokens);
        return ['message' => 'Theme saved'];
    }

    /** GET /settings/permalinks — structures + the real .htaccess on disk. */
    public function getPermalinks() {
        $this->loadPermalinksLib();
        return $this->permalinksPayload();
    }

    /** PUT /settings/permalinks — save structures and merge rewrite rules into .htaccess. */
    public function savePermalinks() {
        $this->loadPermalinksLib();
        $in = $this->body();
        $incoming = $in['structures'] ?? null;
        if (!is_array($incoming)) {
            return ['error' => 'structures must be an object', 'code' => 400];
        }

        $clean = [];
        foreach ($incoming as $type => $struct) {
            $type = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $type));
            $struct = preg_replace('#[^a-z0-9/_{}:-]#i', '', (string) $struct);
            if ($type === '') {
                continue;
            }
            $struct = '/' . ltrim($struct, '/');
            if (strpos($struct, '{slug}') === false) {
                $struct = rtrim($struct, '/') . '/{slug}';
            }
            $clean[$type] = $struct;
        }

        if (!\save_permalink_structures($clean)) {
            return ['error' => 'Could not save permalink settings', 'code' => 500];
        }

        $out = $this->permalinksPayload($clean);
        $wrote = strpos((string) ($out['htaccess']['content'] ?? ''), '# BEGIN Modular Permalinks') !== false;
        if (!$out['htaccess']['writable'] || !$wrote) {
            $out['warning'] = '.htaccess could not be updated. Permalink structures were saved; check file permissions.';
        } else {
            $out['message'] = 'Permalinks saved. .htaccess updated.';
        }
        return $out;
    }

    /* ---- helpers ---- */
    private function loadPermalinksLib(): void {
        $file = (defined('CORE_PATH') ? CORE_PATH : dirname(__DIR__)) . '/functions/permalinks.php';
        if (is_file($file)) {
            require_once $file;
        }
    }

    private function permalinksPayload(?array $structures = null): array {
        $path = \get_htaccess_path();
        $content = is_readable($path) ? (string) file_get_contents($path) : '';
        $exists = is_file($path);

        $resolved = [];
        try {
            $rows = $this->db->query("SELECT slug FROM post_types WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $rows = [];
        }
        foreach ($rows as $row) {
            $slug = (string) $row['slug'];
            if ($structures !== null && isset($structures[$slug])) {
                $resolved[$slug] = $structures[$slug];
            } else {
                $resolved[$slug] = \get_permalink_structure($slug);
            }
        }
        if ($structures !== null) {
            foreach ($structures as $slug => $struct) {
                if (!isset($resolved[$slug])) {
                    $resolved[$slug] = $struct;
                }
            }
        }

        return [
            'structures' => $resolved,
            'site_url' => defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '',
            'htaccess' => [
                'path' => $path,
                'content' => $content,
                'exists' => $exists,
                'writable' => \htaccess_is_writable($path),
                'permalink_rules' => \build_permalink_htaccess_section($resolved),
            ],
        ];
    }

    private function set(string $name, $value): void {
        $stored = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
        $stmt = $this->db->prepare("
            INSERT INTO settings (option_name, option_value, autoload, created_at, updated_at)
            VALUES (?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), updated_at = NOW()
        ");
        $stmt->execute([$name, $stored]);
    }
    private function maybeJson($v) {
        if (!is_string($v)) return $v;
        $t = trim($v);
        if ($t !== '' && ($t[0] === '{' || $t[0] === '[')) { $d = json_decode($t, true); if ($d !== null) return $d; }
        return $v;
    }
    private function body(): array { return json_decode(file_get_contents('php://input'), true) ?: []; }
}
