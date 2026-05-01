<?php

namespace App\Core;

class App
{
    protected static array $store = [];

    public static function boot(): void
    {
        self::loadConfigFiles();
        self::resolveLocale();
        self::resolveSkin();
        Lang::load(self::get('locale', config('app.default_locale', 'cs')));
    }

    public static function set(string $key, mixed $value): void
    {
        self::$store[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$store[$key] ?? $default;
    }

    protected static function loadConfigFiles(): void
    {
        $config = [];

        foreach (glob(config_path('*.php')) as $file) {
            $name = basename($file, '.php');
            $config[$name] = require $file;
        }

        foreach ([base_path('config.php'), base_path('private/config.php')] as $file) {
            if (!is_file($file)) {
                continue;
            }

            $localConfig = require $file;

            if (is_array($localConfig)) {
                $config = self::mergeConfig($config, $localConfig);
            }
        }

        self::set('config', $config);
    }

    protected static function mergeConfig(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (
                array_key_exists($key, $base)
                && is_array($base[$key])
                && is_array($value)
                && !self::isList($base[$key])
                && !self::isList($value)
            ) {
                $base[$key] = self::mergeConfig($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    protected static function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    protected static function resolveLocale(): void
    {
        $defaultLocale = config('app.default_locale', 'cs');
        $availableLocales = config('app.available_locales', ['cs', 'en']);

        $locale = $_GET['lang'] ?? $_SESSION['app_locale'] ?? $_COOKIE['app_locale'] ?? $defaultLocale;

        if (!in_array($locale, $availableLocales, true)) {
            $locale = $defaultLocale;
        }

        $_SESSION['app_locale'] = $locale;

        setcookie('app_locale', $locale, [
            'expires' => time() + 31536000,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);

        self::set('locale', $locale);
    }

    protected static function resolveSkin(): void
    {
        $defaultSkin = config('app.default_skin', 'mac-1984-mono');
        $availableSkins = config('app.available_skins', ['mac-1984-mono']);

        $skin = $_GET['skin'] ?? $_SESSION['app_skin'] ?? $_COOKIE['app_skin'] ?? $defaultSkin;

        if (!in_array($skin, $availableSkins, true)) {
            $skin = $defaultSkin;
        }

        $_SESSION['app_skin'] = $skin;

        setcookie('app_skin', $skin, [
            'expires' => time() + 31536000,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);

        self::set('skin', $skin);
    }
}
