<?php

return [
    'app_name' => 'Public Ethics of Work',
    'app_slug' => 'public-ethics-of-work',
    'app_version' => '0.1.0',

    'default_locale' => 'cs',
    'available_locales' => ['cs', 'en'],

    'default_skin' => 'mac-1984-mono',
    'available_skins' => [
        'mac-1984-mono',
        'win3-gray',
        'amber-terminal',
        'zine-xerox',
    ],

    'sleep_minutes_per_day' => 480,

    'recovery' => [
        'base_minutes' => 30,
        'workload_multiplier' => 0.35,
        'short_window_days' => 7,
        'long_window_days' => 30,
    ],

    'entry_types' => [
        'achievement',
        'fuckup',
        'regen',
        'repair',
    ],

    'visibility_modes' => [
        'private',
        'public',
        'internal',
    ],

    'project_visibility_modes' => [
        'private',
        'public',
        'masked',
    ],

    'reflection_statuses' => [
        'pending',
        'approved',
        'rejected',
    ],

    'site_meta' => [
        'tagline_cs' => 'veřejná etika práce',
        'tagline_en' => 'public ethics of work',
        'description_cs' => 'Open-source pracovní log pro achievementy, fuckupy, regen, projekty a veřejnou reflexi.',
        'description_en' => 'An open-source work log for achievements, fuckups, regen, projects, and public reflection.',
    ],
];
