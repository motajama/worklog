<?php

return [
    'default' => getenv('DB_DRIVER') ?: 'sqlite',

    'connections' => [
        'mysql' => [
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_DATABASE') ?: 'worklog',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'timeout' => 5,
        ],

        'sqlite' => [
            'database' => getenv('DB_SQLITE_PATH') ?: base_path('database/worklog.sqlite'),
        ],
    ],
];
