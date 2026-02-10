<?php
/**
 * Subscription and Dev Mode helpers.
 *
 * Backed by the `settings` table; no extra tables needed.
 */

if (!function_exists('subscription_get_setting')) {
    /**
     * Fetch a setting from the `settings` table (autoloaded or not).
     * Simple cached lookup to avoid repeated queries.
     */
    function subscription_get_setting(string $key, $default = null) {
        static $cache = null;
        if (!isset($GLOBALS['db'])) {
            return $default;
        }
        $db = $GLOBALS['db'];

        // First load all autoload settings once
        if ($cache === null) {
            $cache = [];
            try {
                $stmt = $db->query("SELECT option_name, option_value FROM settings WHERE autoload = 1");
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $cache[$row['option_name']] = $row['option_value'];
                }
            } catch (\Exception $e) {
                // Swallow errors; fall back to direct lookup below
            }
        }

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        // Fallback: single-row lookup (for non-autoload settings)
        try {
            $stmt = $db->prepare("SELECT option_value FROM settings WHERE option_name = ? LIMIT 1");
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
            if ($value !== false) {
                $cache[$key] = $value;
                return $value;
            }
        } catch (\Exception $e) {
            return $default;
        }

        return $default;
    }
}

if (!function_exists('subscription_base_tier')) {
    /**
     * The configured (licensed) tier, ignoring dev-mode override.
     */
    function subscription_base_tier(): string {
        $tier = strtolower((string) subscription_get_setting('license_tier', 'free'));
        if (!in_array($tier, ['free', 'pro', 'agency'], true)) {
            $tier = 'free';
        }
        return $tier;
    }
}

if (!function_exists('dev_mode_enabled')) {
    /**
     * Dev mode flag, stored in settings as '0' / '1'.
     */
    function dev_mode_enabled(): bool {
        $v = subscription_get_setting('dev_mode_enabled', '0');
        return $v === '1' || $v === 1 || $v === true;
    }
}

if (!function_exists('subscription_effective_tier')) {
    /**
     * Effective tier, taking dev-mode override into account.
     *
     * In dev-mode, you can simulate a higher or lower tier via `dev_mode_tier_override`.
     * In production, this should normally be disabled.
     */
    function subscription_effective_tier(): string {
        $base = subscription_base_tier();
        if (!dev_mode_enabled()) {
            return $base;
        }
        $override = strtolower((string) subscription_get_setting('dev_mode_tier_override', ''));
        if (in_array($override, ['free', 'pro', 'agency'], true)) {
            return $override;
        }
        return $base;
    }
}

if (!function_exists('subscription_has_feature')) {
    /**
     * Feature gating by tier.
     *
     * Features:
     *  - core_cms           (always on)
     *  - modular_post_types (Pro+)
     *  - advanced_fields    (Pro+)
     *  - automations        (Pro+)
     *  - revisions_audit    (Pro+)
     *  - advanced_permissions (Pro+)
     *  - white_label        (Agency)
     *  - org_teams          (Agency)
     */
    function subscription_has_feature(string $feature): bool {
        $feature = strtolower($feature);
        $tier = subscription_effective_tier();

        // Super admins should always see all functionality regardless of tier
        if (function_exists('is_admin') && is_admin()) {
            return true;
        }

        // Always-on basics
        if ($feature === 'core_cms') {
            return true;
        }

        // Map features to minimum tier
        $map = [
            'modular_post_types'   => 'pro',
            'advanced_fields'      => 'pro',
            'automations'          => 'pro',
            'revisions_audit'      => 'pro',
            'advanced_permissions' => 'pro',
            'primevue_advanced'    => 'pro',
            'white_label'          => 'agency',
            'org_teams'            => 'agency',
            'template_overrides'   => 'agency',
        ];

        if (!isset($map[$feature])) {
            // Unknown features are treated as disabled
            return false;
        }

        $requiredTier = $map[$feature];
        $order = ['free' => 0, 'pro' => 1, 'agency' => 2];

        return $order[$tier] >= $order[$requiredTier];
    }
}

