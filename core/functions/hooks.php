<?php
/**
 * Hook Helper Functions
 * WordPress-style action and filter functions
 */

use Modular\Hooks;

if (!function_exists('add_action')) {
    /**
     * Add an action hook
     */
    function add_action($hook, $callback, $priority = 10) {
        Hooks::addAction($hook, $callback, $priority);
    }
}

if (!function_exists('do_action')) {
    /**
     * Execute an action
     */
    function do_action($hook, ...$args) {
        Hooks::doAction($hook, ...$args);
    }
}

if (!function_exists('add_filter')) {
    /**
     * Add a filter
     */
    function add_filter($hook, $callback, $priority = 10) {
        Hooks::addFilter($hook, $callback, $priority);
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Apply filters
     */
    function apply_filters($hook, $value, ...$args) {
        return Hooks::applyFilters($hook, $value, ...$args);
    }
}

if (!function_exists('remove_action')) {
    /**
     * Remove an action
     */
    function remove_action($hook, $callback) {
        return Hooks::remove('action', $hook, $callback);
    }
}

if (!function_exists('remove_filter')) {
    /**
     * Remove a filter
     */
    function remove_filter($hook, $callback) {
        return Hooks::remove('filter', $hook, $callback);
    }
}
