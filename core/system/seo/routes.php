<?php
/**
 * System Module: SEO — Route registrations
 */

use Modular\Hooks;

// Register /sitemap.xml public route
Hooks::addFilter('public_routes', function ($routes) {
    if (!is_array($routes)) {
        $routes = [];
    }
    $routes['/sitemap.xml'] = ['handler' => 'seo_sitemap_handler'];
    return $routes;
});

if (!function_exists('seo_sitemap_handler')) {
    function seo_sitemap_handler() {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        // Entries populated via the seo_sitemap_entries filter
        $entries = apply_filters('seo_sitemap_entries', []);
        foreach ($entries as $entry) {
            $loc = htmlspecialchars($entry['loc'] ?? '', ENT_XML1);
            $lastmod = htmlspecialchars($entry['lastmod'] ?? '', ENT_XML1);
            echo "<url><loc>{$loc}</loc>";
            if ($lastmod) {
                echo "<lastmod>{$lastmod}</lastmod>";
            }
            echo '</url>';
        }
        echo '</urlset>';
        exit;
    }
}
