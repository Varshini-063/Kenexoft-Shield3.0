<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, callable|array>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'OPTIONS' => [],
    ];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$this->normalisePath($path)] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$this->normalisePath($path)] = $handler;
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->routes['PUT'][$this->normalisePath($path)] = $handler;
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->routes['DELETE'][$this->normalisePath($path)] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);

        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-CSRF-Token');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            http_response_code(204);
            return;
        }

        $path = $this->normalisePath($this->pathFromUri($uri));
        [$handler, $params] = $this->match($method, $path);

        if (!$handler) {
            if (str_starts_with($path, '/api/')) {
                (new Controller())->json([
                    'success' => false,
                    'message' => 'API route not found.',
                ], 404);
            } else {
                http_response_code(404);
                (new Controller())->render('errors/404', ['title' => 'Page Not Found']);
            }
            return;
        }

        if (is_array($handler)) {
            [$controllerClass, $action] = $handler;
            (new $controllerClass())->$action(...array_values($params));
            return;
        }

        $handler();
    }

    /** @return array{0: callable|array|null, 1: array<string, string>} */
    private function match(string $method, string $path): array
    {
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                return [$handler, $params];
            }
        }

        return [null, []];
    }

    public static function basePath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $directory = str_replace('\\', '/', dirname($scriptName));

        return $directory === '/' ? '' : rtrim($directory, '/');
    }

    private function pathFromUri(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $basePath = self::basePath();

        if ($basePath !== '' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        return $path;
    }

    private function normalisePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        if ($path === '/index.php') {
            return '/';
        }

        return $path === '//' ? '/' : $path;
    }
}
