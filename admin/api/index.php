<?php
/**
 * API Entry Point
 * Routes all /api/* requests.
 * Ensure no PHP output before JSON (disable display_errors for this entry point).
 */
require_once dirname(__DIR__) . '/config/config.php';
ini_set('display_errors', '0');
require_once CORE_PATH . '/functions/database.php';
if (function_exists('run_pending_migrations') && isset($GLOBALS['db'])) {
    run_pending_migrations();
}
require_once __DIR__ . '/../../core/classes/APIRouter.php';
require_once __DIR__ . '/../../core/classes/AuthController.php';
require_once __DIR__ . '/../../core/classes/PostTypesController.php';
require_once __DIR__ . '/../../core/classes/PostsController.php';
require_once CORE_PATH . '/PostTypeResolver.php';
require_once CORE_PATH . '/fields/FieldRegistry.php';
require_once CORE_PATH . '/fields/FieldInterface.php';
require_once __DIR__ . '/../../core/classes/FormsController.php';
require_once __DIR__ . '/../../core/classes/MediaController.php';
require_once __DIR__ . '/../../core/classes/SearchController.php';
require_once __DIR__ . '/../../core/classes/ActionsController.php';
require_once __DIR__ . '/../../core/classes/RateLimiter.php';
require_once __DIR__ . '/../../core/classes/JWT.php';

use Modular\API\Router;
use Modular\API\AuthController;
use Modular\API\PostTypesController;
use Modular\API\PostsController;
use Modular\API\FormsController;
use Modular\API\MediaController;
use Modular\API\SearchController;
use Modular\API\ActionsController;
use Modular\RateLimiter;
use Modular\Auth\JWT;
use Modular\PostTypeResolver;
use Modular\Fields\FieldRegistry;

if (!isset($GLOBALS['db'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$router = new Router($GLOBALS['db']);

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Rate limiting middleware
$router->addMiddleware('rate_limit', function() {
    $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (!RateLimiter::check($identifier, 100, 3600)) {
        return [
            'error' => 'Rate limit exceeded',
            'code' => 429,
            'retry_after' => 3600
        ];
    }
    
    return true;
});

// Auth middleware: JWT (Bearer or cookie) or PHP session (same-origin admin)
$router->addMiddleware('auth', function() {
    $token = null;
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    }
    if (!$token && isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
    }
    if ($token) {
        $jwt = new JWT();
        $payload = $jwt->decode($token);
        if ($payload) {
            $GLOBALS['current_user'] = $payload;
            return true;
        }
    }
    // Fallback: session (e.g. form builder in admin)
    if (!empty($_SESSION['user_id'])) {
        $GLOBALS['current_user'] = [
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? '',
            'name' => $_SESSION['user_name'] ?? '',
        ];
        return true;
    }
    return ['error' => 'Authentication required', 'code' => 401];
});

// Auth routes (no auth required, but rate limited)
$router->addRoute('POST', '/api/v1/auth/login', function() {
    $identifier = ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '_login';
    if (!RateLimiter::check($identifier, 5, 900)) { // 5 attempts per 15 minutes
        return ['error' => 'Too many login attempts. Please try again later.', 'code' => 429];
    }
    
    $controller = new AuthController($GLOBALS['db']);
    $result = $controller->login();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['rate_limit']);

$router->addRoute('POST', '/api/v1/auth/register', function() {
    $controller = new AuthController($GLOBALS['db']);
    $result = $controller->login();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
});

$router->addRoute('POST', '/api/v1/auth/register', function() {
    $controller = new AuthController($GLOBALS['db']);
    $result = $controller->register();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['rate_limit']);

$router->addRoute('POST', '/api/v1/auth/forgot', function() {
    $controller = new AuthController($GLOBALS['db']);
    $result = $controller->forgotPassword();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
});

$router->addRoute('POST', '/api/v1/auth/reset', function() {
    $controller = new AuthController($GLOBALS['db']);
    $result = $controller->resetPassword();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
});

// Protected routes
$router->addRoute('GET', '/api/v1/me', function() {
    $userId = $GLOBALS['current_user']['user_id'] ?? null;
    if (!$userId) {
        return ['error' => 'Unauthorized', 'code' => 401];
    }
    
    $stmt = $GLOBALS['db']->prepare("SELECT id, username, email, first_name, last_name, avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    return $user ?: ['error' => 'User not found', 'code' => 404];
}, ['auth']);

// Post Types routes
$router->addRoute('GET', '/api/v1/post-types', function() {
    $controller = new PostTypesController($GLOBALS['db']);
    return $controller->index();
}, ['auth']);

$router->addRoute('GET', '/api/v1/post-types/{id}', function($id) {
    $controller = new PostTypesController($GLOBALS['db']);
    $result = $controller->show($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('POST', '/api/v1/post-types', function() {
    $controller = new PostTypesController($GLOBALS['db']);
    $result = $controller->create();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('PUT', '/api/v1/post-types/{id}', function($id) {
    $controller = new PostTypesController($GLOBALS['db']);
    $result = $controller->update($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('DELETE', '/api/v1/post-types/{id}', function($id) {
    $controller = new PostTypesController($GLOBALS['db']);
    return $controller->delete($id);
}, ['auth']);

// Fields (for post type builder)
$router->addRoute('GET', '/api/v1/fields', function() {
    FieldRegistry::bootDefaultFields();
    return FieldRegistry::listForAdmin();
}, ['auth']);

// Schema validation
$router->addRoute('POST', '/api/v1/schema/validate', function() {
    $input = json_decode(file_get_contents('php://input'), true);
    $schema = $input['schema'] ?? $input;
    if (empty($schema) || !is_array($schema)) {
        return ['valid' => false, 'errors' => ['Request body must contain a schema object or { "schema": ... }']];
    }
    $resolver = new PostTypeResolver($GLOBALS['db']);
    return $resolver->validateSchema($schema);
}, ['auth']);

// Posts routes
$router->addRoute('GET', '/api/v1/posts', function() {
    $controller = new PostsController($GLOBALS['db']);
    $postTypeId = $_GET['post_type_id'] ?? null;
    return $controller->index($postTypeId);
}, ['auth']);

$router->addRoute('GET', '/api/v1/posts/{id}', function($id) {
    $controller = new PostsController($GLOBALS['db']);
    $result = $controller->show($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('POST', '/api/v1/posts', function() {
    $controller = new PostsController($GLOBALS['db']);
    $result = $controller->create();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('PUT', '/api/v1/posts/{id}', function($id) {
    $controller = new PostsController($GLOBALS['db']);
    $result = $controller->update($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('DELETE', '/api/v1/posts/{id}', function($id) {
    $controller = new PostsController($GLOBALS['db']);
    return $controller->delete($id);
}, ['auth']);

// Forms routes
$router->addRoute('GET', '/api/v1/forms', function() {
    $controller = new FormsController($GLOBALS['db']);
    return $controller->index();
}, ['auth']);

$router->addRoute('GET', '/api/v1/forms/{id}', function($id) {
    $controller = new FormsController($GLOBALS['db']);
    $result = $controller->show($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
});

$router->addRoute('POST', '/api/v1/forms', function() {
    $controller = new FormsController($GLOBALS['db']);
    $result = $controller->create();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('PUT', '/api/v1/forms/{id}', function($id) {
    $controller = new FormsController($GLOBALS['db']);
    $result = $controller->update($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('DELETE', '/api/v1/forms/{id}', function($id) {
    $controller = new FormsController($GLOBALS['db']);
    return $controller->delete($id);
}, ['auth']);

$router->addRoute('POST', '/api/v1/forms/{id}/submit', function($id) {
    $controller = new FormsController($GLOBALS['db']);
    $result = $controller->submit($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
});

// Media routes
$router->addRoute('GET', '/api/v1/media', function() {
    $controller = new MediaController($GLOBALS['db']);
    return $controller->index();
}, ['auth']);

$router->addRoute('GET', '/api/v1/media/{id}', function($id) {
    $controller = new MediaController($GLOBALS['db']);
    $result = $controller->show($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('POST', '/api/v1/media/upload', function() {
    $controller = new MediaController($GLOBALS['db']);
    $result = $controller->upload();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('DELETE', '/api/v1/media/{id}', function($id) {
    $controller = new MediaController($GLOBALS['db']);
    return $controller->delete($id);
}, ['auth']);

// Search routes
$router->addRoute('GET', '/api/v1/search', function() {
    $controller = new SearchController($GLOBALS['db']);
    $result = $controller->search();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('GET', '/api/v1/search/autocomplete', function() {
    $controller = new SearchController($GLOBALS['db']);
    return $controller->autocomplete();
}, ['auth']);

// Actions routes
$router->addRoute('GET', '/api/v1/actions', function() {
    $controller = new ActionsController($GLOBALS['db']);
    return $controller->index();
}, ['auth']);

$router->addRoute('GET', '/api/v1/actions/{id}', function($id) {
    $controller = new ActionsController($GLOBALS['db']);
    $result = $controller->show($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('POST', '/api/v1/actions', function() {
    $controller = new ActionsController($GLOBALS['db']);
    $result = $controller->create();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('PUT', '/api/v1/actions/{id}', function($id) {
    $controller = new ActionsController($GLOBALS['db']);
    $result = $controller->update($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('DELETE', '/api/v1/actions/{id}', function($id) {
    $controller = new ActionsController($GLOBALS['db']);
    return $controller->delete($id);
}, ['auth']);

// Public read-only API (no auth): resolved posts for frontend/themes
$router->addRoute('GET', '/api/v1/public/posts', function() {
    $type = $_GET['type'] ?? $_GET['post_type'] ?? null;
    if (empty($type)) {
        http_response_code(400);
        return ['error' => 'Query parameter "type" (post type slug) is required'];
    }
    $resolver = new PostTypeResolver($GLOBALS['db']);
    $pt = $resolver->getPostType($type);
    if (!$pt) {
        http_response_code(404);
        return ['error' => 'Post type not found'];
    }
    $stmt = $GLOBALS['db']->prepare("
        SELECT p.id, p.slug, p.title, p.excerpt, p.status, p.published_at, p.created_at
        FROM posts p
        INNER JOIN post_types pt ON p.post_type_id = pt.id
        WHERE pt.slug = ? AND p.deleted_at IS NULL AND p.status = 'published'
        ORDER BY p.published_at DESC, p.created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$type]);
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return ['post_type' => $type, 'posts' => $list];
});

$router->addRoute('GET', '/api/v1/public/posts/{slug}', function($slug) {
    $type = $_GET['type'] ?? $_GET['post_type'] ?? null;
    if (empty($type)) {
        $result = ['error' => 'Query parameter "type" (post type slug) is required', 'code' => 400];
        http_response_code(400);
        unset($result['code']);
        return $result;
    }
    $stmt = $GLOBALS['db']->prepare("
        SELECT p.id FROM posts p
        INNER JOIN post_types pt ON p.post_type_id = pt.id
        WHERE pt.slug = ? AND p.slug = ? AND p.deleted_at IS NULL AND p.status = 'published'
    ");
    $stmt->execute([$type, $slug]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$row) {
        $result = ['error' => 'Post not found', 'code' => 404];
        http_response_code(404);
        unset($result['code']);
        return $result;
    }
    $resolver = new PostTypeResolver($GLOBALS['db']);
    $resolved = $resolver->resolvePost((int) $row['id'], ['resolve_relations' => true, 'include_author' => true]);
    if (isset($resolved['error'])) {
        http_response_code($resolved['code'] ?? 500);
        unset($resolved['code']);
        return $resolved;
    }
    return $resolved;
});

// Handle request: normalize path so /modular/admin/api/index.php/api/v1/forms -> /api/v1/forms
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];
if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== '') {
    $path = $_SERVER['PATH_INFO'];
} else {
    $script = $_SERVER['SCRIPT_NAME'];
    if (strpos($path, $script) === 0) {
        $path = substr($path, strlen($script));
    }
}
$path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?: '', '/');
if ($path === '') {
    $path = '/';
}

try {
    $result = $router->handle($method, $path);
    $router->jsonResponse($result);
} catch (Throwable $e) {
    error_log('API error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
    exit;
}
