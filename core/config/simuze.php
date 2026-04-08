<?php
/**
 * SIMEZU auth & payments – config.
 * App API Token (sk_live_...) must be stored server-side only. Override via config.local.php or env.
 * @see SIMEZU embed rules: server-side proxy required; frontend points to proxy only.
 */
if (!defined('SIMEZU_AUTH_ENABLED')) {
    $v = getenv('SIMEZU_AUTH_ENABLED');
    define('SIMEZU_AUTH_ENABLED', $v !== false && $v !== '' ? (bool) $v : true);
}
// Simezu API base URL (no trailing slash). Proxy forwards to this. e.g. https://app.simezu.com/api or http://localhost/simezu/api
if (!defined('SIMEZU_API_BASE')) {
    define('SIMEZU_API_BASE', getenv('SIMEZU_API_BASE') ?: 'http://localhost/simezu/api');
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
// Embed: base URL where SIMEZU assets are served (no trailing slash). For loading simezu-forms.js, simezu-embed.js, avatar.css
// For local development we default to the local Simezu instance; production can override via SIMEZU_EMBED_URL env.
if (!defined('SIMEZU_EMBED_URL')) {
    define('SIMEZU_EMBED_URL', getenv('SIMEZU_EMBED_URL') ?: 'http://localhost/simezu');
}
// App API Token (sk_live_...) from Tokens tab – server-side only, never expose to frontend. Set in config.local.php for production.
if (!defined('SIMEZU_APP_API_TOKEN')) {
    define('SIMEZU_APP_API_TOKEN', getenv('SIMEZU_APP_API_TOKEN') ?: '');
}
// App ID (UUID) and API Key from App settings – for API auth headers
if (!defined('SIMEZU_APP_ID')) {
    define('SIMEZU_APP_ID', getenv('SIMEZU_APP_ID') ?: '');
}
if (!defined('SIMEZU_API_KEY')) {
    define('SIMEZU_API_KEY', getenv('SIMEZU_API_KEY') ?: '');
}
if (!defined('SIMEZU_APP')) {
    define('SIMEZU_APP', getenv('SIMEZU_APP') ?: 'simuze-mdlr');
}
// JWT secret shared with the Simezu auth server. Override in config.local.php for production.
if (!defined('SIMEZU_JWT_SECRET')) {
    define('SIMEZU_JWT_SECRET', getenv('SIMEZU_JWT_SECRET') ?: '');
}
// Login page URL for redirect when unauthenticated. Defaults to the Modular-hosted login route.
if (!defined('SIMEZU_LOGIN_URL')) {
    define('SIMEZU_LOGIN_URL', getenv('SIMEZU_LOGIN_URL') ?: (defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/login' : 'http://localhost/simuze-mdlr/login'));
}
if (!defined('SIMEZU_DASHBOARD_URL')) {
    define('SIMEZU_DASHBOARD_URL', getenv('SIMEZU_DASHBOARD_URL') ?: 'http://localhost/simuze-mdlr/user/{username}');
}
if (!defined('SIMEZU_ACCOUNT_URL')) {
    define('SIMEZU_ACCOUNT_URL', getenv('SIMEZU_ACCOUNT_URL') ?: 'http://localhost/simuze-mdlr/account');
}
// Super administrator user ID in simuze_users — owns all auto-created location groups (city/province/country).
if (!defined('SIMUZE_SUPER_ADMIN_ID')) {
    define('SIMUZE_SUPER_ADMIN_ID', (int) (getenv('SIMUZE_SUPER_ADMIN_ID') ?: 2));
}
