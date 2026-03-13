<?php
/**
 * System Module: Search — Route registrations
 */

use Modular\Hooks;

// Register /search public route
Hooks::addFilter('public_routes', function ($routes) {
    if (!is_array($routes)) {
        $routes = [];
    }
    $routes['/search'] = ['handler' => 'search_public_handler'];
    return $routes;
});

if (!function_exists('search_public_handler')) {
    function search_public_handler() {
        $query = trim($_GET['q'] ?? '');
        $results = apply_filters('search_results', [], $query);
        // Theme is responsible for rendering; pass results via global
        $GLOBALS['search_query']   = $query;
        $GLOBALS['search_results'] = $results;
    }
}
