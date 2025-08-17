<?php
class Router {
    private $routes = [];

    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
    }

    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }

    public function delete($path, $handler) {
        $this->routes['DELETE'][$path] = $handler;
    }

    public function options($path, $handler) {
        $this->routes['OPTIONS'][$path] = $handler;
    }

    public function put($path, $handler) {
        $this->routes['PUT'][$path] = $handler;
    }

    private function matchRoute($pattern, $uri) {
        // Convert route pattern to regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // Remove the full match
            return $matches;
        }
        return false;
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        $basePath = '/examverse_backend/public';
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        // Handle OPTIONS requests immediately
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Max-Age: 86400');
            http_response_code(204);
            exit;
        }

        error_log("Checking route: Method=$method, URI=$uri");
        
        // First check for exact matches
        if (isset($this->routes[$method][$uri])) {
            $handler = $this->routes[$method][$uri];
            $this->executeHandler($handler);
            return;
        }

        // Then check for dynamic routes
        foreach ($this->routes[$method] as $pattern => $handler) {
            if (strpos($pattern, '{') !== false) {
                $params = $this->matchRoute($pattern, $uri);
                if ($params !== false) {
                    $this->executeHandler($handler, $params);
                    return;
                }
            }
        }

        // No route found
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'Route not found', 'method' => $method, 'uri' => $uri]);
    }

    private function executeHandler($handler, $params = []) {
        // Check if handler is an anonymous function
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } else {
            // Handle controller class method
            list($controllerClass, $methodName) = $handler;
            $controllerInstance = new $controllerClass();
            
            if (!empty($params)) {
                // Pass the first parameter (usually the ID) to the method
                $controllerInstance->$methodName($params[0]);
            } else {
                $controllerInstance->$methodName();
            }
        }
    }
}