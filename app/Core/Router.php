<?php

namespace App\Core;

class Router
{
    public static function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = self::normalizePath((string) parse_url($uri, PHP_URL_PATH));

        $routes = config('routes', []);

        foreach ($routes as $route) {
            if (strtoupper($route['method']) !== $method) {
                continue;
            }

            $params = self::match($route['path'], $path);

            if ($params === null) {
                continue;
            }

            App::set('current_route', $route);
            App::set('current_route_name', $route['name'] ?? null);
            App::set('current_route_params', $params);

            self::runMiddleware($route);

            if (!empty($route['handler'])) {
                self::runHandler($route, $params);
                return;
            }

            if (!empty($route['view'])) {
                View::render($route['view'], [
                    'route' => $route,
                    'params' => $params,
                    'page_title' => t($route['title_key'] ?? 'page.home_title'),
                ]);

                return;
            }

            http_response_code(501);
            echo 'Route matched, but no view or handler is implemented yet.';
            return;
        }

        http_response_code(404);

        View::render('errors/404', [
            'page_title' => '404',
            'route' => null,
            'params' => [],
        ]);
    }

    protected static function runMiddleware(array $route): void
    {
        $middlewareStack = $route['middleware'] ?? [];

        foreach ($middlewareStack as $middleware) {
            if ($middleware === 'auth') {
                if (!Auth::check()) {
                    $_SESSION['url.intended'] = $_SERVER['REQUEST_URI'] ?? route_url('admin.dashboard');
                    flash('error', 'Tahle část je jen pro přihlášeného admina.');
                    redirect(route_url('auth.login'));
                }
            }

            if ($middleware === 'guest') {
                if (Auth::check()) {
                    redirect(route_url('admin.dashboard'));
                }
            }
        }
    }

    protected static function runHandler(array $route, array $params = []): void
    {
        $handler = $route['handler'];

        if (!is_array($handler) || count($handler) !== 2) {
            http_response_code(500);
            echo 'Invalid route handler.';
            return;
        }

        [$class, $method] = $handler;

        if (!class_exists($class) || !method_exists($class, $method)) {
            http_response_code(500);
            echo 'Route handler not found.';
            return;
        }

        call_user_func([$class, $method], $params);
    }

    protected static function match(string $routePath, string $requestPath): ?array
    {
        $pattern = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static fn(array $matches) => '(?P<' . $matches[1] . '>[^/]+)',
            $routePath
        );

        $pattern = '#^' . rtrim($pattern, '/') . '$#';

        if ($routePath === '/') {
            $pattern = '#^/$#';
        }

        if (!preg_match($pattern, $requestPath, $matches)) {
            return null;
        }

        $params = [];

        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    protected static function normalizePath(string $path): string
    {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        if ($scriptDir && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }
}
