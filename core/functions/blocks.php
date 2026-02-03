<?php
/**
 * Public block rendering. No schema awareness, no DB access.
 * Renders normalized blocks via theme modules/{type}.php.
 */

if (!function_exists('render_blocks')) {
    /**
     * Render an array of normalized blocks.
     *
     * @param array $blocks From PostTypeResolver::resolvePost()['blocks']
     * @param string $theme_path Path to theme dir (e.g. THEMES_PATH . '/MDLR-2025')
     * @return string HTML
     */
    function render_blocks(array $blocks, string $theme_path = ''): string
    {
        if ($theme_path === '' && defined('THEMES_PATH') && defined('DEFAULT_THEME')) {
            $theme_path = THEMES_PATH . '/' . DEFAULT_THEME;
        }
        $modules_dir = rtrim($theme_path, '/') . '/modules';
        $out = '';
        foreach ($blocks as $block) {
            $type = $block['type'] ?? 'text';
            $module_file = $modules_dir . '/' . $type . '.php';
            if (!is_file($module_file)) {
                $module_file = $modules_dir . '/default.php';
            }
            if (is_file($module_file)) {
                ob_start();
                include $module_file;
                $out .= ob_get_clean();
            }
        }
        return $out;
    }
}
