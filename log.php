<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\App;
use App\Core\View;
use App\Services\PublicLogService;

App::boot();

$data = PublicLogService::build(30, 180);

View::render('public/log-page', $data, false);
