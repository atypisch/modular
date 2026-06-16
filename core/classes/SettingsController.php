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

    /* ---- helpers ---- */
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
