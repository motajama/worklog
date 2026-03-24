<?php

namespace App\Core;

class Lang
{
    public static function load(string $locale): void
    {
        $file = lang_path($locale . '.php');

        if (!file_exists($file)) {
            $file = lang_path(config('app.default_locale', 'cs') . '.php');
        }

        $translations = require $file;

        App::set('lang', $translations);
    }

    public static function get(string $key, ?string $fallback = null): string
    {
        $translations = App::get('lang', []);

        if (array_key_exists($key, $translations)) {
            return (string) $translations[$key];
        }

        return $fallback ?? $key;
    }
}
