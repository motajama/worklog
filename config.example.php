<?php

return [
    'app' => [
        'app_name' => 'Worklog: Public Ethics of Work',
        'app_slug' => 'worklog-public-ethics-of-work',
        'app_version' => '1.0.0',
        'base_path' => '/worklog',

        'footprint' => [
            'token_kg_per_token' => 0.0000000002,
        ],

        'public_log' => [
            'copy' => [
                'cs' => [
                    'title' => 'worklog',
                    'description' => 'Public Czech log intro.',
                    'panel_intro_text' => 'Reflection panel intro.',
                ],
                'en' => [
                    'title' => 'worklog',
                    'description' => 'Public English log intro.',
                    'panel_intro_text' => 'Reflection panel intro.',
                ],
            ],
            'display' => [
                'show_balance_entry_count' => false,
                'show_work_mix_total' => false,
                'show_work_mix_hours' => false,
                'mobile_scroll_reflections' => true,
                'footer_html' => ' <a href="https://example.com">site credit</a>',
            ],
        ],
    ],

    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'worklog',
                'username' => 'worklog',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'timeout' => 5,
            ],
        ],
    ],
];
