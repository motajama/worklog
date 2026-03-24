<?php

namespace App\Core;

RuntimeException;

class View
{
    public static function render(string $view, array $data = [], string|false $layout = 'layout'): void
    {
        $viewFile = views_path($view . '.php');

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View not found: {$view}");
        }

        $content = self::capture($viewFile, $data);

        if ($layout === false) {
            echo $content;
            return;
        }

        $layoutFile = views_path($layout . '.php');

        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout not found: {$layout}");
        }

        extract($data, EXTR_SKIP);
        require $layoutFile;
    }

    protected static function capture(string $file, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
