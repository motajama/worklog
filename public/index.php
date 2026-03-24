<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/app/Helpers/helpers.php';

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

use App\Core\App;
use App\Core\Router;

App::boot();
Router::dispatch();
