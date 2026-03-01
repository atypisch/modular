<?php
/**
 * Simuze auth & payments – config placeholders.
 * Set via environment (.env) or config.local.php; no logic until configured.
 * @see docs/SIMUZE_DATA_MIGRATION.md or SIMUZE_FLOWS.md
 */
if (!defined('SIMEZU_AUTH_ENABLED')) {
    define('SIMEZU_AUTH_ENABLED', getenv('SIMEZU_AUTH_ENABLED') ?: false);
}
if (!defined('SIMEZU_AUTH_API_URL')) {
    define('SIMEZU_AUTH_API_URL', getenv('SIMEZU_AUTH_API_URL') ?: '');
}
if (!defined('SIMEZU_PAYMENTS_ENABLED')) {
    define('SIMEZU_PAYMENTS_ENABLED', getenv('SIMEZU_PAYMENTS_ENABLED') ?: false);
}
if (!defined('SIMEZU_PAYMENTS_API_URL')) {
    define('SIMEZU_PAYMENTS_API_URL', getenv('SIMEZU_PAYMENTS_API_URL') ?: '');
}
