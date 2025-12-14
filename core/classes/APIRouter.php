<?php
/**
 * REST API Router
 * Handles all API requests with versioning
 */

namespace Modular\API;

class Router {
    private $db;
    private $routes = [];
    private $middleware = [];
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function addRoute($method, $path, $handler, $middleware = []) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }
    
    public function addMiddleware($name, $callback) {
        $this->middleware[$name] = $callback;
    }
    
    public function handle($method, $path) {
        $path = parse_url($path, PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = $this->pathToRegex($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                // Run middleware
                foreach ($route['middleware'] as $mwName) {
                    if (isset($this->middleware[$mwName])) {
                        $result = $this->middleware[$mwName]();
                        if ($result !== true) {
                            return $this->jsonResponse($result, 401);
                        }
                    }
                }
                
                // Extract params
                array_shift($matches);
                $params = $matches;
                
                // Call handler
                if (is_callable($route['handler'])) {
                    return call_user_func_array($route['handler'], $params);
                } elseif (is_string($route['handler'])) {
                    list($class, $method) = explode('@', $route['handler']);
                    $instance = new $class($this->db);
                    return call_user_func_array([$instance, $method], $params);
                }
            }
        }
        
        return $this->jsonResponse(['error' => 'Not Found'], 404);
    }
    
    private function pathToRegex($path) {
        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    public function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}

