<?php
/**
 * SIMEZU auth proxy – REQUIRED for form embed.
 * Adds App API Token and forwards requests to Simezu. Frontend must point to this, never to Simezu directly.
 * Token must be stored server-side only (config.local.php or env).
 */
if (!defined('MODULAR_PATH')) {
    define('MODULAR_PATH', dirname(dirname(__DIR__)));
}
require_once MODULAR_PATH . '/admin/config/config.php';
if (is_file(MODULAR_PATH . '/core/config/simuze.php')) {
    require_once MODULAR_PATH . '/core/config/simuze.php';
}

$apiBase = defined('SIMEZU_API_BASE') ? rtrim(SIMEZU_API_BASE, '/') : '';
$appToken = defined('SIMEZU_APP_API_TOKEN') ? SIMEZU_APP_API_TOKEN : '';
$appId = defined('SIMEZU_APP_ID') ? SIMEZU_APP_ID : '';
$apiKey = defined('SIMEZU_API_KEY') ? SIMEZU_API_KEY : '';

if ($apiBase === '' || $appToken === '') {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'SIMEZU proxy not configured (SIMEZU_API_BASE, SIMEZU_APP_API_TOKEN)']);
    exit;
}

// Path after /api/auth[/...] – e.g. login, register, me, forms, users/me
// Supports:
// - rewrite query (?path=...)
// - legacy global injection ($GLOBALS['simezu_auth_path'])
$path = '';
if (isset($_GET['path'])) {
    $path = trim((string) $_GET['path'], '/');
} elseif (isset($GLOBALS['simezu_auth_path'])) {
    $path = trim((string) $GLOBALS['simezu_auth_path'], '/');
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$targetUrl = $apiBase . '/auth' . ($path !== '' ? '/' . $path : '');

$query = $_SERVER['QUERY_STRING'] ?? '';
if ($query !== '') {
    parse_str($query, $qp);
    unset($qp['path']);
    $cleaned = http_build_query($qp);
    if ($cleaned !== '') {
        $targetUrl .= '?' . $cleaned;
    }
}

// Use user token only for user-context endpoints (e.g. /me, /users/*).
// IMPORTANT: Form/embed endpoints must always use app token, otherwise a stale
// browser access token can break /auth/forms with "Missing or invalid App API Token".
$userToken = null;
if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $userToken = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_COOKIE['accessToken']) && $_COOKIE['accessToken'] !== '') {
    $userToken = 'Bearer ' . $_COOKIE['accessToken'];
}

$pathLower = strtolower((string) $path);
$allowUserToken = false;
if ($pathLower === 'me' || strpos($pathLower, 'me/') === 0) {
    $allowUserToken = true;
} elseif (strpos($pathLower, 'users/') === 0 || $pathLower === 'users') {
    $allowUserToken = true;
} elseif ($pathLower === 'roles' || strpos($pathLower, 'roles/') === 0) {
    $allowUserToken = true;
}

$authHeader = ($allowUserToken && $userToken !== null)
    ? $userToken
    : ('Bearer ' . $appToken);

$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-App-ID: ' . $appId,
    'X-API-Key: ' . $apiKey,
    'Authorization: ' . $authHeader,
];

$body = null;
if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
    $body = file_get_contents('php://input');
}

$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
if ($body !== null && $body !== '') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$respHeaders = substr($response, 0, $headerSize);
$respBody = substr($response, $headerSize);

http_response_code($httpCode);

// Forward relevant response headers (e.g. Content-Type)
foreach (explode("\r\n", $respHeaders) as $line) {
    if (stripos($line, 'Content-Type:') === 0) {
        header(trim($line));
        break;
    }
}

// Bridge auth cookies to PHP: simezu-forms.js only writes to localStorage, not cookies.
// Setting cookies here lets PHP detect the logged-in state on the next request.
if ($httpCode === 200 && in_array($pathLower, ['login', 'register', 'magic-link/verify', ''], true)) {
    $decoded = json_decode($respBody, true);
    if (!empty($decoded['accessToken'])) {
        setcookie('accessToken', $decoded['accessToken'], time() + 86400 * 14, '/');
    }
    if (!empty($decoded['refreshToken'])) {
        setcookie('refreshToken', $decoded['refreshToken'], time() + 86400 * 90, '/');
    }
}

echo $respBody;
