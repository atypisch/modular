<?php
/**
 * API Entry Point
 * Routes all /api/* requests
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../core/classes/APIRouter.php';
require_once __DIR__ . '/../../../core/classes/AuthController.php';
require_once __DIR__ . '/../../../core/classes/PostTypesController.php';
require_once __DIR__ . '/../../../core/classes/PostsController.php';
require_once __DIR__ . '/../../../core/classes/FormsController.php';
require_once __DIR__ . '/../../../core/classes/MediaController.php';
require_once __DIR__ . '/../../../core/classes/SearchController.php';
require_once __DIR__ . '/../../../core/classes/ActionsController.php';
require_once __DIR__ . '/../../../core/classes/RateLimiter.php';
require_once __DIR__ . '/../../../core/classes/JWT.php';

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

if (!isset($GLOBALS['db'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
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

// Auth middleware
$router->addMiddleware('auth', function() use ($GLOBALS) {
    $token = null;
    
    // Check Authorization header
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    }
    
    // Check cookie
    if (!$token && isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
    }
    
    if (!$token) {
        return ['error' => 'Authentication required', 'code' => 401];
    }
    
    $jwt = new JWT();
    $payload = $jwt->decode($token);
    
    if (!$payload) {
        return ['error' => 'Invalid token', 'code' => 401];
    }
    
    // Store user in request context
    $GLOBALS['current_user'] = $payload;
    return true;
});

// Auth routes (no auth required, but rate limited)
$router->addRoute('POST', '/api/v1/auth/login', function() use ($GLOBALS) {
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

$router->addRoute('POST', '/api/v1/auth/register', function() use ($GLOBALS) {
    $controller = new AuthController($GLOBALS['db']);
    $result = $controller->login();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
});

$router->addRoute('POST', '/api/v1/auth/register', function() use ($GLOBALS) {
    $controller = new AuthController($GLOBALS['db']);
    $result = $controller->register();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['rate_limit']);

$router->addRoute('POST', '/api/v1/auth/forgot', function() use ($GLOBALS) {
    $controller = new AuthController($GLOBALS['db']);
    $result = $controller->forgotPassword();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
});

$router->addRoute('POST', '/api/v1/auth/reset', function() use ($GLOBALS) {
    $controller = new AuthController($GLOBALS['db']);
    $result = $controller->resetPassword();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
});

// Protected routes
$router->addRoute('GET', '/api/v1/me', function() use ($GLOBALS) {
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
$router->addRoute('GET', '/api/v1/post-types', function() use ($GLOBALS) {
    $controller = new PostTypesController($GLOBALS['db']);
    return $controller->index();
}, ['auth']);

$router->addRoute('GET', '/api/v1/post-types/{id}', function($id) use ($GLOBALS) {
    $controller = new PostTypesController($GLOBALS['db']);
    $result = $controller->show($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('POST', '/api/v1/post-types', function() use ($GLOBALS) {
    $controller = new PostTypesController($GLOBALS['db']);
    $result = $controller->create();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('PUT', '/api/v1/post-types/{id}', function($id) use ($GLOBALS) {
    $controller = new PostTypesController($GLOBALS['db']);
    $result = $controller->update($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('DELETE', '/api/v1/post-types/{id}', function($id) use ($GLOBALS) {
    $controller = new PostTypesController($GLOBALS['db']);
    return $controller->delete($id);
}, ['auth']);

// Posts routes
$router->addRoute('GET', '/api/v1/posts', function() use ($GLOBALS) {
    $controller = new PostsController($GLOBALS['db']);
    $postTypeId = $_GET['post_type_id'] ?? null;
    return $controller->index($postTypeId);
}, ['auth']);

$router->addRoute('GET', '/api/v1/posts/{id}', function($id) use ($GLOBALS) {
    $controller = new PostsController($GLOBALS['db']);
    $result = $controller->show($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('POST', '/api/v1/posts', function() use ($GLOBALS) {
    $controller = new PostsController($GLOBALS['db']);
    $result = $controller->create();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('PUT', '/api/v1/posts/{id}', function($id) use ($GLOBALS) {
    $controller = new PostsController($GLOBALS['db']);
    $result = $controller->update($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('DELETE', '/api/v1/posts/{id}', function($id) use ($GLOBALS) {
    $controller = new PostsController($GLOBALS['db']);
    return $controller->delete($id);
}, ['auth']);

// Forms routes
$router->addRoute('GET', '/api/v1/forms', function() use ($GLOBALS) {
    $controller = new FormsController($GLOBALS['db']);
    return $controller->index();
}, ['auth']);

$router->addRoute('GET', '/api/v1/forms/{id}', function($id) use ($GLOBALS) {
    $controller = new FormsController($GLOBALS['db']);
    $result = $controller->show($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
});

$router->addRoute('POST', '/api/v1/forms', function() use ($GLOBALS) {
    $controller = new FormsController($GLOBALS['db']);
    $result = $controller->create();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('PUT', '/api/v1/forms/{id}', function($id) use ($GLOBALS) {
    $controller = new FormsController($GLOBALS['db']);
    $result = $controller->update($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('DELETE', '/api/v1/forms/{id}', function($id) use ($GLOBALS) {
    $controller = new FormsController($GLOBALS['db']);
    return $controller->delete($id);
}, ['auth']);

$router->addRoute('POST', '/api/v1/forms/{id}/submit', function($id) use ($GLOBALS) {
    $controller = new FormsController($GLOBALS['db']);
    $result = $controller->submit($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
});

// Media routes
$router->addRoute('GET', '/api/v1/media', function() use ($GLOBALS) {
    $controller = new MediaController($GLOBALS['db']);
    return $controller->index();
}, ['auth']);

$router->addRoute('GET', '/api/v1/media/{id}', function($id) use ($GLOBALS) {
    $controller = new MediaController($GLOBALS['db']);
    $result = $controller->show($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('POST', '/api/v1/media/upload', function() use ($GLOBALS) {
    $controller = new MediaController($GLOBALS['db']);
    $result = $controller->upload();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('DELETE', '/api/v1/media/{id}', function($id) use ($GLOBALS) {
    $controller = new MediaController($GLOBALS['db']);
    return $controller->delete($id);
}, ['auth']);

// Search routes
$router->addRoute('GET', '/api/v1/search', function() use ($GLOBALS) {
    $controller = new SearchController($GLOBALS['db']);
    $result = $controller->search();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('GET', '/api/v1/search/autocomplete', function() use ($GLOBALS) {
    $controller = new SearchController($GLOBALS['db']);
    return $controller->autocomplete();
}, ['auth']);

// Actions routes
$router->addRoute('GET', '/api/v1/actions', function() use ($GLOBALS) {
    $controller = new ActionsController($GLOBALS['db']);
    return $controller->index();
}, ['auth']);

$router->addRoute('GET', '/api/v1/actions/{id}', function($id) use ($GLOBALS) {
    $controller = new ActionsController($GLOBALS['db']);
    $result = $controller->show($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('POST', '/api/v1/actions', function() use ($GLOBALS) {
    $controller = new ActionsController($GLOBALS['db']);
    $result = $controller->create();
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('PUT', '/api/v1/actions/{id}', function($id) use ($GLOBALS) {
    $controller = new ActionsController($GLOBALS['db']);
    $result = $controller->update($id);
    if (isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
}, ['auth']);

$router->addRoute('DELETE', '/api/v1/actions/{id}', function($id) use ($GLOBALS) {
    $controller = new ActionsController($GLOBALS['db']);
    return $controller->delete($id);
}, ['auth']);

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

$result = $router->handle($method, $path);
$router->jsonResponse($result);
