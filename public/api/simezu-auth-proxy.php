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

// Path after /api/auth[/...] – e.g. login, register, me, auth/me
$path = isset($GLOBALS['simezu_auth_path']) ? $GLOBALS['simezu_auth_path'] : '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$targetUrl = $apiBase . '/auth' . ($path !== '' ? '/' . $path : '');

$query = $_SERVER['QUERY_STRING'] ?? '';
if ($query !== '') {
    $targetUrl .= '?' . $query;
}

// Use user token when present (e.g. /me, /roles); otherwise app token for server-side (login, register)
$userToken = null;
if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $userToken = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_COOKIE['accessToken']) && $_COOKIE['accessToken'] !== '') {
    $userToken = 'Bearer ' . $_COOKIE['accessToken'];
}
$authHeader = $userToken !== null ? $userToken : ('Bearer ' . $appToken);

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

echo $respBody;
