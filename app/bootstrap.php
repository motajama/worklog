<?php

declare(strict_types=1);

require_once __DIR__ . '/Helpers/helpers.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = app_path(str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php');

    if (file_exists($file)) {
        require_once $file;
    }
});
