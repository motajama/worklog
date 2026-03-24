<?php

return [
    'public' => [
        ['label_key' => 'nav.home', 'route' => 'home'],
        ['label_key' => 'nav.method', 'route' => 'method'],
        ['label_key' => 'nav.about', 'route' => 'about'],
    ],

    'admin' => [
        ['label_key' => 'nav.dashboard', 'route' => 'admin.dashboard'],
        ['label_key' => 'nav.entries', 'route' => 'admin.entries.index'],
        ['label_key' => 'nav.projects', 'route' => 'admin.projects.index'],
        ['label_key' => 'nav.reflections', 'route' => 'admin.reflections.index'],
        ['label_key' => 'nav.settings', 'route' => 'admin.settings'],
    ],
];
