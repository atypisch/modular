<?php
/**
 * Simuze payments – minimal stub for future integration.
 * No logic until SIMEZU_PAYMENTS_ENABLED and SIMEZU_PAYMENTS_API_URL are set (e.g. via .env).
 */
namespace Modular;

class SimezuPayments {
    public static function isEnabled(): bool {
        return defined('SIMEZU_PAYMENTS_ENABLED') && SIMEZU_PAYMENTS_ENABLED && defined('SIMEZU_PAYMENTS_API_URL') && SIMEZU_PAYMENTS_API_URL !== '';
    }

    public static function getApiUrl(): string {
        return defined('SIMEZU_PAYMENTS_API_URL') ? (string) SIMEZU_PAYMENTS_API_URL : '';
    }
}
