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

// ---------------------------------------------------------------------------
// Modular-native hook aliases (md_ prefix). Use these in new code.
// The underlying engine is identical — Modular\Hooks.
// ---------------------------------------------------------------------------
if (!function_exists('md_hook_register')) {
    /** Register a handler for a named event. */
    function md_hook_register(string $hook, callable $callback, int $priority = 10): void {
        Hooks::addAction($hook, $callback, $priority);
    }
}

if (!function_exists('md_hook_action')) {
    /**
     * Backwards-compatible alias for registering an action handler.
     * Used by some Simuze modules; prefer md_hook_register() in new code.
     */
    function md_hook_action(string $hook, $callback, int $priority = 10): void {
        // Accept both callable and string callback names.
        if (function_exists('md_hook_register')) {
            md_hook_register($hook, $callback, $priority);
        } else {
            Hooks::addAction($hook, $callback, $priority);
        }
    }
}

if (!function_exists('md_hook_run')) {
    /** Fire all handlers registered for a named event. */
    function md_hook_run(string $hook, ...$args): void {
        Hooks::doAction($hook, ...$args);
    }
}

if (!function_exists('md_filter_register')) {
    /** Register a filter for a named value. */
    function md_filter_register(string $hook, callable $callback, int $priority = 10): void {
        Hooks::addFilter($hook, $callback, $priority);
    }
}

if (!function_exists('md_filter_apply')) {
    /** Apply all registered filters to a value. */
    function md_filter_apply(string $hook, $value, ...$args) {
        return Hooks::applyFilters($hook, $value, ...$args);
    }
}
