<?php
/**
 * Simuze auth – minimal stub for external auth API.
 * No logic until SIMEZU_AUTH_ENABLED and SIMEZU_AUTH_API_URL are set (e.g. via .env).
 */
namespace Modular;

class SimezuAuth {
    public static function isEnabled(): bool {
        return defined('SIMEZU_AUTH_ENABLED') && SIMEZU_AUTH_ENABLED && defined('SIMEZU_AUTH_API_URL') && SIMEZU_AUTH_API_URL !== '';
    }

    public static function getApiUrl(): string {
        return defined('SIMEZU_AUTH_API_URL') ? (string) SIMEZU_AUTH_API_URL : '';
    }
}
