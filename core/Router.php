<?php

declare(strict_types=1);

/**
 * Exception thrown to halt execution after a redirect.
 * Used internally by controllers to stop execution after sending redirect headers.
 */
class RedirectException extends RuntimeException
{
}

/**
 * Router - Simple Request Dispatcher
 * 
 * Maps HTTP method + path combinations to handler functions or controller methods.
 * Supports GET and POST methods with a simple route registration system.
 * 
 * Usage:
 * $router->get('/path', [Controller::class, 'method']);
 * $router->post('/path', [Controller::class, 'method']);
 * $router->dispatch();
 */
class Router
{
    /**
     * Registered routes organized by HTTP method.
     * 
     * @var array<string, array<string, callable|array>>
     */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    /**
     * Register a GET route.
     * 
     * @param string $path URL path (e.g., '/dashboard')
     * @param callable|array{0: class-string, 1: string} $handler Closure or [ControllerClass, 'method']
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$this->normalizePath($path)] = $handler;
    }

    /**
     * Register a POST route.
     * 
     * @param string $path URL path (e.g., '/login')
     * @param callable|array{0: class-string, 1: string} $handler Closure or [ControllerClass, 'method']
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$this->normalizePath($path)] = $handler;
    }

    /**
     * Dispatch the current request to the matched route handler.
     * 
     * Extracts the HTTP method and path from $_SERVER, looks up the matching route,
     * and invokes the handler. Returns 404 if no route matches.
     * 
     * @return void
     */
    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $this->normalizePath($path);

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '404 - Page introuvable';
            return;
        }

        try {
            $response = $this->callHandler($handler);
        } catch (RedirectException) {
            // Redirect was sent, stop execution
            return;
        }

        if ($response !== null) {
            echo $response;
        }
    }

    /**
     * Invoke a route handler (closure or controller method).
     * 
     * @param callable|array{0: class-string, 1: string} $handler The route handler
     * @return mixed Handler response (usually void for controllers)
     */
    private function callHandler(callable|array $handler): mixed
    {
        // If handler is [ControllerClass, 'method'], instantiate and call
        if (is_array($handler) && isset($handler[0], $handler[1]) && is_string($handler[0])) {
            $controller = new $handler[0]();
            return $controller->{$handler[1]}();
        }

        // If handler is a closure, call it directly
        return call_user_func($handler);
    }

    /**
     * Normalize a URL path to a consistent format.
     * 
     * Ensures path starts with '/', trims trailing slashes (except for root).
     * 
     * @param string $path Raw URL path
     * @return string Normalized path
     */
    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
