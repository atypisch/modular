<?php
/**
 * Helper functions for the admin interface
 */

if (!function_exists('admin_base_url')) {
    /** Return admin base URL (no trailing slash). Use for links and redirects. */
    function admin_base_url() {
        return defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin';
    }
}

if (!function_exists('check_active_path')) {
    /**
     * Check if the current page matches a given path
     */
    function check_active_path($path) {
        $current_uri = $_SERVER['REQUEST_URI'];
        // Remove query string for comparison
        $current_path = strtok($current_uri, '?');
        $check_path = strtok($path, '?');
        return ($current_path == $check_path || strpos($current_path, $check_path) !== false);
    }
}

if (!function_exists('is_post_types_page')) {
    /**
     * Check if we're on a post types admin page
     * Exclude posts pages (which also contain 'post' in the path)
     */
    function is_post_types_page() {
        $uri = $_SERVER['REQUEST_URI'];
        // Check if we're on a post-types page, but NOT on a posts page
        $isPostTypes = strpos($uri, '/admin/post-types') !== false;
        $isPosts = strpos($uri, '/admin/posts') !== false;
        return $isPostTypes && !$isPosts;
    }
}

if (!function_exists('is_media_page')) {
    /**
     * Check if we're on a media admin page
     */
    function is_media_page() {
        return strpos($_SERVER['REQUEST_URI'], '/admin/media') !== false;
    }
}

if (!function_exists('is_users_page')) {
    /**
     * Check if we're on a users admin page
     */
    function is_users_page() {
        return strpos($_SERVER['REQUEST_URI'], '/admin/users') !== false;
    }
}

if (!function_exists('is_settings_page')) {
    /**
     * Check if we're on a settings admin page
     */
    function is_settings_page() {
        return strpos($_SERVER['REQUEST_URI'], '/admin/settings') !== false;
    }
}

if (!function_exists('is_profile_page')) {
    /**
     * Check if we're on a profile admin page
     */
    function is_profile_page() {
        return strpos($_SERVER['REQUEST_URI'], '/admin/profile') !== false;
    }
}

if (!function_exists('is_forms_page')) {
    /**
     * Check if we're on a forms admin page
     */
    function is_forms_page() {
        return strpos($_SERVER['REQUEST_URI'], '/admin/forms') !== false;
    }
}

if (!function_exists('is_actions_page')) {
    /**
     * Check if we're on an actions admin page
     */
    function is_actions_page() {
        return strpos($_SERVER['REQUEST_URI'], '/admin/actions') !== false;
    }
}

if (!function_exists('is_modules_page')) {
    /**
     * Check if we're on a modules admin page
     */
    function is_modules_page() {
        return strpos($_SERVER['REQUEST_URI'], '/admin/modules') !== false;
    }
}

if (!function_exists('is_module_active')) {
    /**
     * Check if a module is active by slug.
     */
    function is_module_active($slug) {
        $m = $GLOBALS['module_manager'] ?? null;
        return $m && $m->isModuleActive($slug);
    }
}

if (!function_exists('is_posts_page')) {
    /**
     * Check if we're on a posts admin page
     * This should NOT match post-types pages
     */
    function is_posts_page() {
        $uri = $_SERVER['REQUEST_URI'];
        // Check if we're on a posts page, but NOT on a post-types page
        $isPosts = strpos($uri, '/admin/posts') !== false;
        $isPostTypes = strpos($uri, '/admin/post-types') !== false;
        return $isPosts && !$isPostTypes;
    }
}

if (!function_exists('admin_posts_filter_remember')) {
    /** Remember or clear the posts list post-type filter (session). */
    function admin_posts_filter_remember(?int $postTypeId): void {
        if ($postTypeId !== null && $postTypeId > 0) {
            $_SESSION['posts_filter_post_type_id'] = $postTypeId;
            return;
        }
        unset($_SESSION['posts_filter_post_type_id']);
    }
}

if (!function_exists('admin_posts_filter_get')) {
    /** Last selected post-type filter for the posts list, or null for "all". */
    function admin_posts_filter_get(): ?int {
        if (empty($_SESSION['posts_filter_post_type_id'])) {
            return null;
        }
        $id = (int) $_SESSION['posts_filter_post_type_id'];
        return $id > 0 ? $id : null;
    }
}

if (!function_exists('admin_posts_list_status_from_request')) {
    /** Normalise ?status= query param for posts list views. */
    function admin_posts_list_status_from_request(?string $raw): string {
        $allowed = ['draft', 'scheduled', 'trash'];
        return ($raw !== null && in_array($raw, $allowed, true)) ? $raw : '';
    }
}

if (!function_exists('admin_posts_list_count')) {
    /** Count posts for a list view (draft, scheduled, trash), optionally per post type. */
    function admin_posts_list_count(\PDO $db, ?int $postTypeId, string $listStatus): int {
        if (!in_array($listStatus, ['draft', 'scheduled', 'trash'], true)) {
            return 0;
        }
        try {
            if ($listStatus === 'trash') {
                $sql = 'SELECT COUNT(*) FROM posts WHERE deleted_at IS NOT NULL';
            } else {
                $sql = "SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND status = '" . ($listStatus === 'draft' ? 'draft' : 'scheduled') . "'";
            }
            $params = [];
            if ($postTypeId) {
                $sql .= ' AND post_type_id = ?';
                $params[] = $postTypeId;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('admin_posts_index_url')) {
    /**
     * URL for the posts overview. Pass a post type id to filter; pass null to use
     * the remembered filter from the session (falls back to unfiltered list).
     * $listStatus: null/'' = all active, or draft|scheduled|trash.
     */
    function admin_posts_index_url(?int $postTypeId = null, ?string $listStatus = null): string {
        if ($postTypeId === null) {
            $postTypeId = admin_posts_filter_get();
        }
        $query = [];
        if ($postTypeId !== null && $postTypeId > 0) {
            $query['post_type_id'] = $postTypeId;
        }
        $listStatus = admin_posts_list_status_from_request($listStatus);
        if ($listStatus !== '') {
            $query['status'] = $listStatus;
        }
        if ($query === []) {
            return admin_url('posts');
        }
        return admin_url('posts?' . http_build_query($query));
    }
}

if (!function_exists('admin_posts_permanently_delete')) {
    /**
     * Hard-delete posts that are already in trash. Returns number of rows removed.
     */
    function admin_posts_permanently_delete(\PDO $db, array $ids): int {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) {
            return $id > 0;
        })));
        if ($ids === []) {
            return 0;
        }

        $removed = 0;
        foreach ($ids as $id) {
            $stmt = $db->prepare('SELECT id, parent_id FROM posts WHERE id = ? AND deleted_at IS NOT NULL LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                continue;
            }

            $parentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            $reparent = $db->prepare('UPDATE posts SET parent_id = :parent WHERE parent_id = :id');
            $reparent->bindValue(':parent', $parentId, $parentId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
            $reparent->bindValue(':id', $id, \PDO::PARAM_INT);
            $reparent->execute();

            $db->prepare('DELETE FROM post_terms WHERE post_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM post_revisions WHERE post_id = ?')->execute([$id]);

            $del = $db->prepare('DELETE FROM posts WHERE id = ? AND deleted_at IS NOT NULL');
            $del->execute([$id]);
            if ($del->rowCount() > 0) {
                $removed++;
            }
        }

        return $removed;
    }
}

if (!function_exists('admin_posts_resolve_post_type_id')) {
    /**
     * Resolve post_type_id from request param or by looking up a post row.
     */
    function admin_posts_resolve_post_type_id(?int $postId = null, $requestParam = null): ?int {
        if ($requestParam !== null && $requestParam !== '' && $requestParam !== 'all' && $requestParam !== '0') {
            $id = (int) $requestParam;
            if ($id > 0) {
                return $id;
            }
        }
        $remembered = admin_posts_filter_get();
        if ($remembered !== null) {
            return $remembered;
        }
        if ($postId !== null && $postId > 0 && isset($GLOBALS['db'])) {
            try {
                $stmt = $GLOBALS['db']->prepare('SELECT post_type_id FROM posts WHERE id = ? LIMIT 1');
                $stmt->execute([$postId]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row && !empty($row['post_type_id'])) {
                    return (int) $row['post_type_id'];
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }
        return null;
    }
}
