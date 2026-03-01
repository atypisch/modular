<?php
return [
    'title' => 'Template system',
    'body' => <<<'MD'
Templates are PHP files in `public/themes/{theme}/`. Rendering is SSR (PHP); Vue only for interactive components.

**Template helpers (available in themes):**
- `get_site_name()` – site name from settings
- `get_menu($location)` – nav items (from settings or pages)
- `get_posts($args)` – list of posts (post_type, posts_per_page, orderby, order)
- `get_post($id, $slug)` – single post by id or slug
- `get_meta($post_id, $key)` – post meta value
- `theme_uri($path)` – URL to theme asset
- `e($string)` – escape for HTML

**PHP loop example:**
```php
foreach (get_posts(['post_type' => 'post', 'posts_per_page' => 10]) as $post) {
    echo '<h2>' . e($post['title']) . '</h2>';
}
```

**Vue (optional):** Mount only on elements that need reactivity; do not control routing.
MD
,
];
