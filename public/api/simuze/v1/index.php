<?php
/**
 * Minimal Modular REST bridge for /api/simuze/v1/*
 * Supports the endpoints used by profile/group settings screens.
 */

if (!defined('MODULAR_PATH')) {
    define('MODULAR_PATH', dirname(dirname(dirname(dirname(__DIR__)))));
}
if (!defined('SIMUZE_MODULAR_RUN')) {
    define('SIMUZE_MODULAR_RUN', true);
}
if (!defined('ABSPATH')) {
    define('ABSPATH', MODULAR_PATH . '/');
}

require_once MODULAR_PATH . '/admin/config/config.php';
require_once MODULAR_PATH . '/admin/bootstrap.php';

if (is_file(MODULAR_PATH . '/core/config/simuze.php')) {
    require_once MODULAR_PATH . '/core/config/simuze.php';
}
if (is_file(MODULAR_PATH . '/core/functions/http.php')) {
    require_once MODULAR_PATH . '/core/functions/http.php';
}
if (is_file(MODULAR_PATH . '/core/functions/database.php')) {
    require_once MODULAR_PATH . '/core/functions/database.php';
}

require_once MODULAR_PATH . '/public/modules/simuze-community/includes/modular-bridge.php';
require_once MODULAR_PATH . '/public/modules/simuze-community/includes/api/class-rest-api.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

class Simuze_Modular_REST_Request {
    private $params;
    private $json;
    private $files;
    private $headers;
    private $contentType;

    public function __construct(array $params, array $json, array $files) {
        $this->params      = $params;
        $this->json        = $json;
        $this->files       = $files;
        $this->headers     = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $ct                = isset($_SERVER['CONTENT_TYPE']) ? (string) $_SERVER['CONTENT_TYPE'] : '';
        $this->contentType = ['value' => $ct];
    }

    public function get_param($key) {
        return $this->params[$key] ?? null;
    }
    public function get_params() {
        return $this->params;
    }
    public function get_json_params() {
        return $this->json;
    }
    public function get_file_params() {
        return $this->files;
    }
    public function get_content_type() {
        return $this->contentType;
    }
    public function get_header($name) {
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }
        $needle = strtolower((string) $name);
        foreach ($this->headers as $k => $v) {
            if (strtolower((string) $k) === $needle) {
                return $v;
            }
        }
        return null;
    }
}

function simuze_rest_bridge_out($result): void {
    if ($result instanceof Simuze_REST_Response) {
        echo json_encode($result->get_data());
        return;
    }
    if ($result instanceof WP_Error) {
        $status = 400;
        if (method_exists($result, 'get_error_data')) {
            $data = $result->get_error_data();
            if (is_array($data) && !empty($data['status'])) {
                $status = (int) $data['status'];
            }
        }
        http_response_code($status);
        echo json_encode([
            'code'    => method_exists($result, 'get_error_code') ? $result->get_error_code() : 'error',
            'message' => method_exists($result, 'get_error_message') ? $result->get_error_message() : 'Unknown error',
        ]);
        return;
    }
    echo json_encode($result);
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$uri    = (string) ($_SERVER['REQUEST_URI'] ?? '');
$base   = '/api/simuze/v1/';
$pos    = strpos($uri, $base);
$route  = $pos !== false ? substr($uri, $pos + strlen($base)) : '';
$route  = trim((string) strtok($route, '?'), '/');

$rawBody = file_get_contents('php://input');
$jsonBody = [];
if (is_string($rawBody) && $rawBody !== '') {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $jsonBody = $decoded;
    }
}

$params = array_merge($_GET ?? [], $_POST ?? [], $jsonBody);
$api    = Simuze_REST_API::get_instance();

if ($route === 'groups/check-unique-handle' && $method === 'POST') {
    $request = new Simuze_Modular_REST_Request($params, $jsonBody, $_FILES ?? []);
    simuze_rest_bridge_out($api->check_unique_handle($request));
    exit;
}

if (preg_match('#^groups/(\d+)/payment-settings$#', $route, $m)) {
    $params['id'] = (int) $m[1];
    $request = new Simuze_Modular_REST_Request($params, $jsonBody, $_FILES ?? []);
    if ($method === 'GET') {
        simuze_rest_bridge_out($api->get_group_payment_settings($request));
        exit;
    }
    if ($method === 'POST') {
        simuze_rest_bridge_out($api->save_group_payment_settings($request));
        exit;
    }
}

if (preg_match('#^groups/(\d+)/payment-settings/test$#', $route, $m) && $method === 'POST') {
    $params['id'] = (int) $m[1];
    $request = new Simuze_Modular_REST_Request($params, $jsonBody, $_FILES ?? []);
    simuze_rest_bridge_out($api->test_group_payment($request));
    exit;
}

if (preg_match('#^groups/(\d+)$#', $route, $m) && $method === 'PUT') {
    $params['id'] = (int) $m[1];
    $request = new Simuze_Modular_REST_Request($params, $jsonBody, $_FILES ?? []);
    simuze_rest_bridge_out($api->update_group($request));
    exit;
}

http_response_code(404);
echo json_encode([
    'code'    => 'rest_route_not_found',
    'message' => 'Route not found',
    'route'   => $route,
    'method'  => $method,
]);
