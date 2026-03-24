<?php

use App\Core\App;
use App\Core\Lang;

if (!function_exists('data_get')) {
    function data_get(array $array, ?string $key, mixed $default = null): mixed
    {
        if ($key === null || $key === '') {
            return $array;
        }

        $segments = explode('.', $key);
        $value = $array;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $base;
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return base_path('app' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return app_path('Config' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('lang_path')) {
    function lang_path(string $path = ''): string
    {
        return app_path('Lang' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('views_path')) {
    function views_path(string $path = ''): string
    {
        return app_path('Views' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        $config = App::get('config', []);
        return data_get($config, $key, $default);
    }
}

if (!function_exists('app_state')) {
    function app_state(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null || $key === '') {
            return App::get('state', $default);
        }

        return App::get($key, $default);
    }
}

if (!function_exists('t')) {
    function t(string $key, ?string $fallback = null): string
    {
        return Lang::get($key, $fallback);
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        if ($base === '/' || $base === '.') {
            $base = '';
        }

        return $base . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return base_url($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return base_url(ltrim($path, '/'));
    }
}

if (!function_exists('current_locale')) {
    function current_locale(): string
    {
        return App::get('locale', config('app.default_locale', 'cs'));
    }
}

if (!function_exists('current_skin')) {
    function current_skin(): string
    {
        return App::get('skin', config('app.default_skin', 'mac-1984-mono'));
    }
}

if (!function_exists('routes')) {
    function routes(): array
    {
        return config('routes', []);
    }
}

if (!function_exists('find_route')) {
    function find_route(string $name): ?array
    {
        foreach (routes() as $route) {
            if (($route['name'] ?? null) === $name) {
                return $route;
            }
        }

        return null;
    }
}

if (!function_exists('route_url')) {
    function route_url(string $name, array $params = []): string
    {
        $route = find_route($name);

        if (!$route) {
            return '#';
        }

        $path = $route['path'];

        $path = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $matches) use ($params) {
                $key = $matches[1];
                return isset($params[$key]) ? rawurlencode((string) $params[$key]) : $matches[0];
            },
            $path
        );

        return url(ltrim($path, '/'));
    }
}

if (!function_exists('current_route')) {
    function current_route(): ?array
    {
        return App::get('current_route');
    }
}

if (!function_exists('current_route_name')) {
    function current_route_name(): ?string
    {
        return App::get('current_route_name');
    }
}

if (!function_exists('current_route_params')) {
    function current_route_params(): array
    {
        return App::get('current_route_params', []);
    }
}

if (!function_exists('route_is')) {
    function route_is(string $name): bool
    {
        return current_route_name() === $name;
    }
}

if (!function_exists('is_admin_route')) {
    function is_admin_route(): bool
    {
        $name = current_route_name();
        return is_string($name) && str_starts_with($name, 'admin.');
    }
}

if (!function_exists('navigation_items')) {
    function navigation_items(): array
    {
        $group = is_admin_route() ? 'admin' : 'public';
        return config('navigation.' . $group, []);
    }
}
