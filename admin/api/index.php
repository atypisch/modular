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
require_once __DIR__ . '/../../core/classes/TaxonomiesController.php';
require_once __DIR__ . '/../../core/classes/SettingsController.php';
require_once __DIR__ . '/../../core/classes/SystemController.php';
require_once __DIR__ . '/../../core/classes/ResolveController.php';
require_once __DIR__ . '/../../core/classes/FieldGroupsController.php';
require_once __DIR__ . '/../../core/classes/ComponentsController.php';
require_once __DIR__ . '/../../core/classes/UpdateController.php';
require_once __DIR__ . '/../../core/classes/StatsController.php';
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
use Modular\API\TaxonomiesController;
use Modular\API\SettingsController;
use Modular\API\SystemController;
use Modular\API\ResolveController;
use Modular\API\FieldGroupsController;
use Modular\API\ComponentsController;
use Modular\API\UpdateController;
use Modular\API\StatsController;
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

// Re-login from the admin SPA when the session expired (a 401 popped the login
// modal). Re-establishes the SAME cms_* session keys as admin/auth/process_login.php
// and returns a fresh CSRF token so the SPA can carry on where it left off.
// No CSRF gate (the expired session's token is stale by definition); rate limited.
$router->addRoute('POST', '/api/v1/auth/relogin', function() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!RateLimiter::check($ip . '_relogin', 8, 900)) {
        return ['error' => 'Too many attempts. Please try again later.', 'code' => 429];
    }
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $email = trim((string) ($input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    if ($email === '' || $password === '') {
        return ['error' => 'Email and password are required', 'code' => 400];
    }
    $db = $GLOBALS['db'];
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([$email]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, $user['password'])) {
        return ['error' => 'Invalid email or password', 'code' => 401];
    }
    if (($user['status'] ?? 'active') !== 'active') {
        return ['error' => 'Account is not active', 'code' => 403];
    }
    $role = 'user';
    try {
        $rs = $db->prepare("SELECT r.name FROM user_roles ur INNER JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ? LIMIT 1");
        $rs->execute([$user['id']]);
        $role = $rs->fetchColumn() ?: 'user';
    } catch (\PDOException $e) { /* default 'user' */ }

    // Same session keys as admin/auth/process_login.php — cms_* are the ONLY keys
    // that grant Modular CMS access (core/functions/auth.php).
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['username']   = $user['username'] ?? $user['email'];
    $_SESSION['user_name']  = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['username'] ?? $user['email']);
    $_SESSION['user_role']  = $role;
    $_SESSION['cms_authenticated'] = true;
    $_SESSION['cms_user_id']       = $user['id'];
    $_SESSION['cms_user_role']     = $role;
    session_regenerate_id(true);
    try {
        $db->prepare("UPDATE users SET last_login = NOW(), last_ip = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$ip, $user['id']]);
    } catch (\PDOException $e) { /* non-fatal */ }

    if (!function_exists('md_csrf_token') && defined('CORE_PATH')) {
        require_once CORE_PATH . '/functions/csrf.php';
    }
    return [
        'ok'   => true,
        'csrf' => function_exists('md_csrf_token') ? md_csrf_token() : '',
        'user' => [
            'id'    => $user['id'],
            'name'  => $_SESSION['user_name'],
            'email' => $user['email'],
            'role'  => $role,
        ],
    ];
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
    
    $stmt = $GLOBALS['db']->prepare("SELECT id, username, email, first_name, last_name, avatar, role, status, bio, last_login, created_at FROM users WHERE id = ?");
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

$router->addRoute('PUT', '/api/v1/media/{id}', function($id) {
    $controller = new MediaController($GLOBALS['db']);
    $result = $controller->update($id);
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

// ---------------------------------------------------------------------------
// Fase 2 — admin SPA endpoints (taxonomies, settings/theme, system, resolve)
// ---------------------------------------------------------------------------
$mkResult = function ($result) {
    if (is_array($result) && isset($result['code'])) {
        http_response_code($result['code']);
        unset($result['code']);
    }
    return $result;
};

// Taxonomies + terms
$router->addRoute('GET', '/api/v1/taxonomies', function() { return (new TaxonomiesController($GLOBALS['db']))->index(); }, ['auth']);
$router->addRoute('POST', '/api/v1/taxonomies', function() use ($mkResult) { return $mkResult((new TaxonomiesController($GLOBALS['db']))->create()); }, ['auth']);
$router->addRoute('GET', '/api/v1/taxonomies/{id}', function($id) use ($mkResult) { return $mkResult((new TaxonomiesController($GLOBALS['db']))->show($id)); }, ['auth']);
$router->addRoute('PUT', '/api/v1/taxonomies/{id}', function($id) use ($mkResult) { return $mkResult((new TaxonomiesController($GLOBALS['db']))->update($id)); }, ['auth']);
$router->addRoute('DELETE', '/api/v1/taxonomies/{id}', function($id) use ($mkResult) { return $mkResult((new TaxonomiesController($GLOBALS['db']))->delete($id)); }, ['auth']);
$router->addRoute('GET', '/api/v1/taxonomies/{id}/terms', function($id) { return (new TaxonomiesController($GLOBALS['db']))->terms($id); }, ['auth']);
$router->addRoute('POST', '/api/v1/taxonomies/{id}/terms', function($id) use ($mkResult) { return $mkResult((new TaxonomiesController($GLOBALS['db']))->createTerm($id)); }, ['auth']);
$router->addRoute('PUT', '/api/v1/taxonomies/{id}/terms/reorder', function($id) { return (new TaxonomiesController($GLOBALS['db']))->reorderTerms($id); }, ['auth']);
$router->addRoute('PUT', '/api/v1/taxonomies/{id}/terms/{termId}', function($id, $termId) use ($mkResult) { return $mkResult((new TaxonomiesController($GLOBALS['db']))->updateTerm($id, $termId)); }, ['auth']);
$router->addRoute('DELETE', '/api/v1/taxonomies/{id}/terms/{termId}', function($id, $termId) { return (new TaxonomiesController($GLOBALS['db']))->deleteTerm($id, $termId); }, ['auth']);

// Settings + theme persistence
$router->addRoute('GET', '/api/v1/settings', function() { return (new SettingsController($GLOBALS['db']))->index(); }, ['auth']);
$router->addRoute('PUT', '/api/v1/settings', function() use ($mkResult) { return $mkResult((new SettingsController($GLOBALS['db']))->update()); }, ['auth']);
$router->addRoute('GET', '/api/v1/settings/theme', function() { return (new SettingsController($GLOBALS['db']))->getTheme(); }, ['auth']);
$router->addRoute('PUT', '/api/v1/settings/theme', function() { return (new SettingsController($GLOBALS['db']))->setTheme(); }, ['auth']);

// System: users, roles, capabilities, modules
$router->addRoute('POST', '/api/v1/posts/reorder', function() use ($mkResult) {
    return $mkResult((new PostsController($GLOBALS['db']))->reorder());
}, ['auth']);

$router->addRoute('GET', '/api/v1/users', function() { return (new SystemController($GLOBALS['db']))->users(); }, ['auth']);
$router->addRoute('POST', '/api/v1/users', function() use ($mkResult) { return $mkResult((new SystemController($GLOBALS['db']))->createUser()); }, ['auth']);
$router->addRoute('PUT', '/api/v1/users/{id}', function($id) use ($mkResult) { return $mkResult((new SystemController($GLOBALS['db']))->updateUser($id)); }, ['auth']);
$router->addRoute('GET', '/api/v1/users/{id}/content', function($id) use ($mkResult) { return $mkResult((new SystemController($GLOBALS['db']))->userContent($id)); }, ['auth']);
$router->addRoute('DELETE', '/api/v1/users/{id}', function($id) use ($mkResult) {
    $me = $_SESSION['cms_user_id'] ?? ($GLOBALS['current_user']['user_id'] ?? null);
    return $mkResult((new SystemController($GLOBALS['db']))->deleteUser($id, $me));
}, ['auth']);
$router->addRoute('GET', '/api/v1/roles', function() { return (new SystemController($GLOBALS['db']))->roles(); }, ['auth']);
$router->addRoute('POST', '/api/v1/roles', function() use ($mkResult) { return $mkResult((new SystemController($GLOBALS['db']))->createRole()); }, ['auth']);
$router->addRoute('PUT', '/api/v1/roles/{id}/capabilities', function($id) use ($mkResult) { return $mkResult((new SystemController($GLOBALS['db']))->updateRoleCapabilities($id)); }, ['auth']);
$router->addRoute('DELETE', '/api/v1/roles/{id}', function($id) use ($mkResult) { return $mkResult((new SystemController($GLOBALS['db']))->deleteRole($id)); }, ['auth']);
$router->addRoute('GET', '/api/v1/capabilities', function() { return (new SystemController($GLOBALS['db']))->capabilities(); }, ['auth']);
$router->addRoute('GET', '/api/v1/modules', function() { return (new SystemController($GLOBALS['db']))->modules(); }, ['auth']);
$router->addRoute('PUT', '/api/v1/modules/{id}', function($id) use ($mkResult) { return $mkResult((new SystemController($GLOBALS['db']))->toggleModule($id)); }, ['auth']);

// System update — one central upgrade point (version check + run core update)
$router->addRoute('GET', '/api/v1/system/update', function() { return (new UpdateController($GLOBALS['db']))->status(); }, ['auth']);
$router->addRoute('POST', '/api/v1/system/update', function() use ($mkResult) { return $mkResult((new UpdateController($GLOBALS['db']))->run()); }, ['auth']);

// Stats, Analytics & Reports — real database statistics
$router->addRoute('GET', '/api/v1/stats/overview', function() { return (new StatsController($GLOBALS['db']))->overview(); }, ['auth']);
$router->addRoute('GET', '/api/v1/stats/analytics', function() { return (new StatsController($GLOBALS['db']))->analytics(); }, ['auth']);
$router->addRoute('GET', '/api/v1/stats/reports', function() { return (new StatsController($GLOBALS['db']))->reports(); }, ['auth']);

// Resolve: effective fieldset + revisions per post
$router->addRoute('GET', '/api/v1/posts/{id}/resolved-fieldset', function($id) use ($mkResult) { return $mkResult((new ResolveController($GLOBALS['db']))->resolvedFieldset($id)); }, ['auth']);
$router->addRoute('GET', '/api/v1/posts/{id}/revisions', function($id) { return (new ResolveController($GLOBALS['db']))->revisions($id); }, ['auth']);

// Field groups (WHERE/binding layer)
$router->addRoute('GET', '/api/v1/field-groups', function() { return (new FieldGroupsController($GLOBALS['db']))->index(); }, ['auth']);
$router->addRoute('POST', '/api/v1/field-groups', function() use ($mkResult) { return $mkResult((new FieldGroupsController($GLOBALS['db']))->create()); }, ['auth']);
$router->addRoute('GET', '/api/v1/field-groups/{id}', function($id) use ($mkResult) { return $mkResult((new FieldGroupsController($GLOBALS['db']))->show($id)); }, ['auth']);
$router->addRoute('PUT', '/api/v1/field-groups/{id}/location', function($id) use ($mkResult) { return $mkResult((new FieldGroupsController($GLOBALS['db']))->updateLocation($id)); }, ['auth']);
$router->addRoute('PUT', '/api/v1/field-groups/{id}', function($id) use ($mkResult) { return $mkResult((new FieldGroupsController($GLOBALS['db']))->update($id)); }, ['auth']);
$router->addRoute('DELETE', '/api/v1/field-groups/{id}', function($id) use ($mkResult) { return $mkResult((new FieldGroupsController($GLOBALS['db']))->delete($id)); }, ['auth']);

// Components (versioned blocks)
$router->addRoute('GET', '/api/v1/components', function() { return (new ComponentsController($GLOBALS['db']))->index(); }, ['auth']);
$router->addRoute('POST', '/api/v1/components', function() use ($mkResult) { return $mkResult((new ComponentsController($GLOBALS['db']))->create()); }, ['auth']);
$router->addRoute('GET', '/api/v1/components/{id}/versions', function($id) { return (new ComponentsController($GLOBALS['db']))->versions($id); }, ['auth']);
$router->addRoute('POST', '/api/v1/components/{id}/versions', function($id) use ($mkResult) { return $mkResult((new ComponentsController($GLOBALS['db']))->createVersion($id)); }, ['auth']);
$router->addRoute('GET', '/api/v1/components/{id}/usage', function($id) { return (new ComponentsController($GLOBALS['db']))->usage($id); }, ['auth']);
$router->addRoute('GET', '/api/v1/components/{id}', function($id) use ($mkResult) { return $mkResult((new ComponentsController($GLOBALS['db']))->show($id)); }, ['auth']);
$router->addRoute('PUT', '/api/v1/components/{id}', function($id) use ($mkResult) { return $mkResult((new ComponentsController($GLOBALS['db']))->update($id)); }, ['auth']);
$router->addRoute('DELETE', '/api/v1/components/{id}', function($id) use ($mkResult) { return $mkResult((new ComponentsController($GLOBALS['db']))->delete($id)); }, ['auth']);

// Export / Import — content bundles (.zip) between Modular installs.
$router->addRoute('GET', '/api/v1/export', function() {
    require_once CORE_PATH . '/functions/export.php';
    require_once CORE_PATH . '/classes/Exporter.php';
    $profile = trim((string) ($_GET['profile'] ?? ''));
    $postTypeSlug = trim((string) ($_GET['post_type'] ?? $_GET['post_type_slug'] ?? ''));
    if ($profile === 'blog_posts') {
        $profile = 'post_type';
        if ($postTypeSlug === '') {
            $postTypeSlug = 'post';
        }
    }
    $exportProfile = null;
    $exportPostType = null;
    if ($profile === 'post_type' && $postTypeSlug !== '' && function_exists('md_export_register_post_type')) {
        try {
            if (function_exists('md_export_clear_registry')) {
                md_export_clear_registry();
            }
            md_export_register_post_type($postTypeSlug);
            $exportProfile = 'post_type';
            $exportPostType = md_export_sanitize_post_type_slug($postTypeSlug);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    } elseif (function_exists('md_export_register_core')) {
        md_export_register_core();
    }
    $includeMedia = !empty($_GET['include_media']);
    $exporter = new \Modular\Exporter($includeMedia, null, $exportProfile, $exportPostType);
    $exporter->streamDownload(); // streams the zip and exit()s
}, ['auth']);

$router->addRoute('POST', '/api/v1/import', function() {
    if (!isset($_FILES['bundle']) || $_FILES['bundle']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        return ['error' => 'No bundle file uploaded'];
    }
    if (strtolower(pathinfo($_FILES['bundle']['name'], PATHINFO_EXTENSION)) !== 'zip') {
        http_response_code(400);
        return ['error' => 'Bundle must be a .zip file'];
    }
    require_once CORE_PATH . '/functions/export.php';
    require_once CORE_PATH . '/classes/Importer.php';
    if (function_exists('md_export_register_core')) { md_export_register_core(); }

    $mode = in_array($_POST['mode'] ?? '', ['skip', 'overwrite', 'dryrun'], true) ? $_POST['mode'] : 'skip';
    $dup  = ($_POST['duplicate_policy'] ?? '') === 'overwrite' ? 'overwrite' : 'skip';
    $userId = $_SESSION['cms_user_id'] ?? ($GLOBALS['current_user']['user_id'] ?? null);

    try {
        $importer = new \Modular\Importer($_FILES['bundle']['tmp_name']);
        $importer->validate();
        $summary = $importer->run($mode, $userId ? (int) $userId : null, $dup);
        return ['success' => true, 'mode' => $mode, 'summary' => $summary];
    } catch (\Throwable $e) {
        http_response_code(422);
        return ['error' => $e->getMessage()];
    }
}, ['auth']);

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
