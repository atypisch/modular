<?php
/**
 * Export/Import registry — modules describe which tables to export,
 * how to dedup on import, and which columns are foreign keys.
 *
 * Modules call md_export_register() in their init hook. The Exporter
 * walks the registry in dependency order; the Importer applies fk_map
 * after building each table's old-id → new-id map.
 */

if (!function_exists('md_export_register')) {
    /**
     * Register a table for export/import.
     *
     * Config keys:
     *  - table        (string, required)              SQL table name
     *  - depends_on   (string[])                      Names of registries imported first
     *  - natural_key  (string[]|null)                 Columns used to dedup on import
     *                                                 — null means insert-only (no skip/overwrite)
     *  - fk_map       (array<string, string>)         column => target registry name
     *  - module       (string|null)                   Module slug (purely informational)
     *  - skip_columns (string[])                      Columns to omit from export (e.g. transient)
     *  - where        (string|null)                   Optional SQL WHERE filter (no params)
     */
    function md_export_register(string $name, array $config): void {
        if (!isset($GLOBALS['md_export_registry']) || !is_array($GLOBALS['md_export_registry'])) {
            $GLOBALS['md_export_registry'] = [];
        }
        $config['name']         = $name;
        $config['depends_on']   = $config['depends_on']   ?? [];
        $config['natural_key']  = $config['natural_key']  ?? null;
        $config['fk_map']       = $config['fk_map']       ?? [];
        $config['module']       = $config['module']       ?? null;
        $config['skip_columns'] = $config['skip_columns'] ?? [];
        $config['where']        = $config['where']        ?? null;
        $GLOBALS['md_export_registry'][$name] = $config;
    }
}

if (!function_exists('md_export_clear_registry')) {
    function md_export_clear_registry(): void {
        $GLOBALS['md_export_registry'] = [];
    }
}

if (!function_exists('md_export_registry')) {
    /**
     * Return the registry sorted in topological order so that
     * dependencies are imported first. Throws on cycles.
     *
     * @return array<string, array>  name => config
     */
    function md_export_registry(): array {
        $reg   = $GLOBALS['md_export_registry'] ?? [];
        if (empty($reg)) return [];

        $sorted   = [];
        $visited  = [];
        $visiting = [];

        $visit = function (string $name) use (&$visit, &$reg, &$sorted, &$visited, &$visiting) {
            if (isset($visited[$name]))  return;
            if (isset($visiting[$name])) {
                throw new \RuntimeException("Circular export dependency at: {$name}");
            }
            if (!isset($reg[$name])) return; // dep references unknown table — skip silently
            $visiting[$name] = true;
            foreach ($reg[$name]['depends_on'] as $dep) {
                $visit($dep);
            }
            $visited[$name] = true;
            unset($visiting[$name]);
            $sorted[$name]  = $reg[$name];
        };

        foreach (array_keys($reg) as $n) {
            $visit($n);
        }
        return $sorted;
    }
}

if (!function_exists('md_export_register_core')) {
    /**
     * Register all core CMS tables. Called once from admin/bootstrap.php
     * before do_action('init'), so modules can register more in their init hook.
     */
    function md_export_register_core(): void {
        // Identity
        md_export_register('roles', [
            'table'       => 'roles',
            'natural_key' => ['name'],
        ]);
        md_export_register('capabilities', [
            'table'       => 'capabilities',
            'natural_key' => ['name'],
        ]);
        md_export_register('users', [
            'table'        => 'users',
            'natural_key'  => ['email'],
            'skip_columns' => ['reset_password_token', 'reset_password_expires',
                               'two_factor_secret', 'email_verification_token',
                               'last_login', 'last_ip'],
        ]);
        md_export_register('role_capabilities', [
            'table'       => 'role_capabilities',
            'depends_on'  => ['roles', 'capabilities'],
            'natural_key' => ['role_id', 'capability_id'],
            'fk_map'      => ['role_id' => 'roles', 'capability_id' => 'capabilities'],
        ]);
        md_export_register('user_roles', [
            'table'       => 'user_roles',
            'depends_on'  => ['users', 'roles'],
            'natural_key' => ['user_id', 'role_id'],
            'fk_map'      => ['user_id' => 'users', 'role_id' => 'roles'],
        ]);

        // Content type definitions
        md_export_register('post_types', [
            'table'       => 'post_types',
            'depends_on'  => ['users'],
            'natural_key' => ['slug'],
            'fk_map'      => ['created_by' => 'users', 'updated_by' => 'users'],
        ]);
        md_export_register('taxonomies', [
            'table'       => 'taxonomies',
            'depends_on'  => ['users'],
            'natural_key' => ['slug'],
            'fk_map'      => ['created_by' => 'users', 'updated_by' => 'users'],
        ]);
        md_export_register('terms', [
            'table'       => 'terms',
            'depends_on'  => ['taxonomies', 'users'],
            'natural_key' => ['taxonomy_id', 'slug'],
            'fk_map'      => ['taxonomy_id' => 'taxonomies', 'parent_id' => 'terms',
                              'created_by' => 'users', 'updated_by' => 'users'],
        ]);
        md_export_register('post_type_taxonomies', [
            'table'       => 'post_type_taxonomies',
            'depends_on'  => ['post_types', 'taxonomies'],
            'natural_key' => ['post_type_id', 'taxonomy_id'],
            'fk_map'      => ['post_type_id' => 'post_types', 'taxonomy_id' => 'taxonomies'],
        ]);
        md_export_register('fields', [
            'table'       => 'fields',
            'depends_on'  => ['post_types', 'users'],
            'natural_key' => ['post_type_id', 'slug'],
            'fk_map'      => ['post_type_id' => 'post_types',
                              'created_by' => 'users', 'updated_by' => 'users'],
        ]);
        md_export_register('field_groups', [
            'table'       => 'field_groups',
            'depends_on'  => ['users'],
            'natural_key' => ['name'],
            'fk_map'      => ['created_by' => 'users', 'updated_by' => 'users'],
        ]);
        md_export_register('component_definitions', [
            'table'       => 'component_definitions',
            'depends_on'  => ['post_types'],
            'natural_key' => ['post_type_id', 'slug'],
            'fk_map'      => ['post_type_id' => 'post_types'],
        ]);

        // Forms
        md_export_register('forms', [
            'table'       => 'forms',
            'depends_on'  => ['users'],
            'natural_key' => ['slug'],
            'fk_map'      => ['created_by' => 'users', 'updated_by' => 'users'],
        ]);
        md_export_register('form_submissions', [
            'table'       => 'form_submissions',
            'depends_on'  => ['forms'],
            'natural_key' => null, // insert-only; no portable dedup key
            'fk_map'      => ['form_id' => 'forms'],
        ]);

        // Media (independent of posts; posts reference media via featured_image)
        md_export_register('media', [
            'table'       => 'media',
            'depends_on'  => ['users'],
            'natural_key' => ['file_path'],
            'fk_map'      => ['uploaded_by' => 'users', 'created_by' => 'users', 'updated_by' => 'users'],
        ]);

        // Posts and relations
        md_export_register('posts', [
            'table'       => 'posts',
            'depends_on'  => ['post_types', 'users', 'media'],
            'natural_key' => ['post_type_id', 'slug'],
            'fk_map'      => [
                'post_type_id'   => 'post_types',
                'parent_id'      => 'posts',
                'featured_image' => 'media',
                'author_id'      => 'users',
                'created_by'     => 'users',
                'updated_by'     => 'users',
            ],
        ]);
        md_export_register('post_meta', [
            'table'       => 'post_meta',
            'depends_on'  => ['posts', 'fields'],
            'natural_key' => ['post_id', 'field_id'],
            'fk_map'      => ['post_id' => 'posts', 'field_id' => 'fields'],
        ]);
        md_export_register('post_terms', [
            'table'       => 'post_terms',
            'depends_on'  => ['posts', 'terms'],
            'natural_key' => ['post_id', 'term_id'],
            'fk_map'      => ['post_id' => 'posts', 'term_id' => 'terms'],
        ]);
        md_export_register('post_revisions', [
            'table'       => 'post_revisions',
            'depends_on'  => ['posts', 'users'],
            'natural_key' => null, // history; insert-only
            'fk_map'      => ['post_id' => 'posts', 'created_by' => 'users'],
        ]);

        // Site-wide config
        md_export_register('options', [
            'table'       => 'options',
            'natural_key' => ['option_name'],
        ]);
        md_export_register('settings', [
            'table'       => 'settings',
            'natural_key' => ['option_name'],
        ]);
    }
}

if (!function_exists('md_export_sanitize_post_type_slug')) {
    function md_export_sanitize_post_type_slug(string $slug): string {
        $slug = trim($slug);
        if ($slug === '' || !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $slug)) {
            throw new \InvalidArgumentException('Invalid post type slug');
        }
        return $slug;
    }
}

if (!function_exists('md_export_register_post_type')) {
    /**
     * Export/import profile: one post type and its related rows.
     * Overwrites overlapping core registry entries with scoped WHERE filters.
     */
    function md_export_register_post_type(string $slug): void {
        $slug = md_export_sanitize_post_type_slug($slug);
        $quotedSlug = md_db()->quote($slug);
        $postTypeId = "(SELECT id FROM post_types WHERE slug = {$quotedSlug} AND deleted_at IS NULL LIMIT 1)";
        $postsFilter = "post_type_id = {$postTypeId} AND deleted_at IS NULL";
        $postIds = "(SELECT id FROM posts WHERE {$postsFilter})";

        // NOTE: post-type bundles deliberately do NOT ship the `users` table.
        // Author identity travels as email on each post row (Exporter::attachAuthorEmails)
        // and is relinked to existing users on import (Importer::importTable).

        md_export_register('post_types', [
            'table'       => 'post_types',
            'natural_key' => ['slug'],
            'where'       => "slug = {$quotedSlug} AND deleted_at IS NULL",
        ]);
        md_export_register('taxonomies', [
            'table'       => 'taxonomies',
            'natural_key' => ['slug'],
            'where'       => "id IN (SELECT taxonomy_id FROM post_type_taxonomies WHERE post_type_id = {$postTypeId})",
        ]);
        md_export_register('terms', [
            'table'       => 'terms',
            'depends_on'  => ['taxonomies'],
            'natural_key' => ['taxonomy_id', 'slug'],
            'where'       => "taxonomy_id IN (SELECT taxonomy_id FROM post_type_taxonomies WHERE post_type_id = {$postTypeId})",
            'fk_map'      => [
                'taxonomy_id' => 'taxonomies',
                'parent_id'   => 'terms',
            ],
        ]);
        md_export_register('post_type_taxonomies', [
            'table'       => 'post_type_taxonomies',
            'depends_on'  => ['post_types', 'taxonomies'],
            'natural_key' => ['post_type_id', 'taxonomy_id'],
            'where'       => "post_type_id = {$postTypeId}",
            'fk_map'      => ['post_type_id' => 'post_types', 'taxonomy_id' => 'taxonomies'],
        ]);
        md_export_register('fields', [
            'table'       => 'fields',
            'depends_on'  => ['post_types'],
            'natural_key' => ['post_type_id', 'slug'],
            'where'       => "post_type_id = {$postTypeId}",
            'fk_map'      => [
                'post_type_id' => 'post_types',
            ],
        ]);
        md_export_register('component_definitions', [
            'table'       => 'component_definitions',
            'depends_on'  => ['post_types'],
            'natural_key' => ['post_type_id', 'slug'],
            'where'       => "post_type_id = {$postTypeId}",
            'fk_map'      => ['post_type_id' => 'post_types'],
        ]);
        md_export_register('media', [
            'table'       => 'media',
            'natural_key' => ['file_path'],
            'where'       => "id IN (SELECT DISTINCT featured_image FROM posts WHERE {$postsFilter} AND featured_image IS NOT NULL)",
        ]);
        md_export_register('posts', [
            'table'       => 'posts',
            'depends_on'  => ['post_types', 'media'],
            'natural_key' => ['post_type_id', 'slug'],
            'where'       => $postsFilter,
            'fk_map'      => [
                'post_type_id'   => 'post_types',
                'parent_id'      => 'posts',
                'featured_image' => 'media',
            ],
        ]);
        md_export_register('post_meta', [
            'table'       => 'post_meta',
            'depends_on'  => ['posts', 'fields'],
            'natural_key' => ['post_id', 'field_id'],
            'where'       => "post_id IN {$postIds}",
            'fk_map'      => ['post_id' => 'posts', 'field_id' => 'fields'],
        ]);
        md_export_register('post_terms', [
            'table'       => 'post_terms',
            'depends_on'  => ['posts', 'terms'],
            'natural_key' => ['post_id', 'term_id'],
            'where'       => "post_id IN {$postIds}",
            'fk_map'      => ['post_id' => 'posts', 'term_id' => 'terms'],
        ]);
        md_export_register('post_revisions', [
            'table'       => 'post_revisions',
            'depends_on'  => ['posts'],
            'natural_key' => null,
            'where'       => "post_id IN {$postIds}",
            'fk_map'      => ['post_id' => 'posts'],
        ]);
    }
}

if (!function_exists('md_export_register_blog_posts')) {
    /** @deprecated Use md_export_register_post_type('post') */
    function md_export_register_blog_posts(): void {
        md_export_register_post_type('post');
    }
}
