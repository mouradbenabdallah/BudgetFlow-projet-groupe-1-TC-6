<?php

declare(strict_types=1);

/**
 * Exception thrown to halt execution after a redirect.
 */
class RedirectException extends RuntimeException
{
}

/**
 * Router
 *
 * Simple request dispatcher that maps HTTP method + path to
 * callable handlers or controller class/method pairs.
 */
class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    /**
     * Register a GET route.
     *
     * @param string $path URL path
     * @param callable|array{0: class-string, 1: string} $handler Closure or [ControllerClass, 'method']
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$this->normalizePath($path)] = $handler;
    }

    /**
     * Register a POST route.
     *
     * @param string $path URL path
     * @param callable|array{0: class-string, 1: string} $handler Closure or [ControllerClass, 'method']
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$this->normalizePath($path)] = $handler;
    }

    /**
     * Dispatch the current request to the matched route handler.
     * Returns 404 if no route matches.
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
     * @return mixed Handler response
     */
    private function callHandler(callable|array $handler): mixed
    {
        if (is_array($handler) && isset($handler[0], $handler[1]) && is_string($handler[0])) {
            $controller = new $handler[0]();

            return $controller->{$handler[1]}();
        }

        return call_user_func($handler);
    }

    /**
     * Normalize a URL path to a consistent format.
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
