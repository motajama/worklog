<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Router;

App::boot();
Router::dispatch();
