<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Services\FootprintService;

App::boot();

$created = FootprintService::generateRecurringInstances(
    date('Y-m-d', strtotime('-7 days')),
    date('Y-m-d', strtotime('+35 days'))
);

echo 'Generated recurring footprint instances: ' . $created . PHP_EOL;
