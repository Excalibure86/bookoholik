<?php

namespace App;

/**
 * Simple Router for the API
 */
class Router
{
    private array $routes = [];
    private array $middleware = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove trailing slash
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }

        foreach ($this->routes as $route) {
            $params = $this->matchRoute($route['path'], $uri);
            if ($params !== false && $route['method'] === $method) {
                // Run middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $mw = new $middlewareClass();
                    $result = $mw->handle();
                    if ($result === false) {
                        return;
                    }
                }

                // Call handler
                [$controllerClass, $action] = $route['handler'];
                $controller = new $controllerClass();
                $controller->$action($params);
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo json_encode(['error' => 'Route not found', 'path' => $uri]);
    }

    private function matchRoute(string $routePath, string $uri): array|false
    {
        // Convert route params like {id} to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Filter only named matches
            return array_filter($matches, fn($key) => !is_numeric($key), ARRAY_FILTER_USE_KEY);
        }

        return false;
    }
}
