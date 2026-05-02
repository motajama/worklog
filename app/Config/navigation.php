<?php

return [
    'public' => [
    ],

    'admin' => [
        ['label_key' => 'nav.dashboard', 'route' => 'admin.dashboard'],
        ['label_key' => 'nav.entries', 'route' => 'admin.entries.index'],
        ['label_key' => 'nav.footprint', 'route' => 'admin.footprint.index'],
        ['label_key' => 'nav.projects', 'route' => 'admin.projects.index'],
        ['label_key' => 'nav.reflections', 'route' => 'admin.reflections.index'],
        ['label_key' => 'nav.settings', 'route' => 'admin.settings'],
    ],
];
