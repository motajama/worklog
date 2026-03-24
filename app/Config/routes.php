<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public routes
    |--------------------------------------------------------------------------
    */

    [
        'method' => 'GET',
        'path' => '/',
        'name' => 'home',
        'view' => 'pages/home',
        'title_key' => 'page.home_title',
    ],
    [
        'method' => 'GET',
        'path' => '/archive',
        'name' => 'archive',
        'view' => 'pages/placeholder',
        'title_key' => 'page.archive_title',
    ],
    [
        'method' => 'GET',
        'path' => '/month/{year}/{month}',
        'name' => 'month.show',
        'view' => 'pages/placeholder',
        'title_key' => 'page.archive_title',
    ],

    [
        'method' => 'GET',
        'path' => '/projects',
        'name' => 'projects.index',
        'view' => 'pages/placeholder',
        'title_key' => 'page.projects_title',
    ],
    [
        'method' => 'GET',
        'path' => '/project/{slug}',
        'name' => 'projects.show',
        'view' => 'pages/placeholder',
        'title_key' => 'page.projects_title',
    ],

    [
        'method' => 'GET',
        'path' => '/fuckups',
        'name' => 'fuckups.index',
        'view' => 'pages/placeholder',
        'title_key' => 'page.fuckups_title',
    ],
    [
        'method' => 'GET',
        'path' => '/fuckup/{slug}',
        'name' => 'fuckups.show',
        'view' => 'pages/placeholder',
        'title_key' => 'page.fuckups_title',
    ],

    [
        'method' => 'GET',
        'path' => '/reflections',
        'name' => 'reflections.index',
        'view' => 'pages/placeholder',
        'title_key' => 'page.reflections_title',
    ],
    [
        'method' => 'POST',
        'path' => '/fuckup/{slug}/reflection',
        'name' => 'reflections.store',
    ],

    [
        'method' => 'GET',
        'path' => '/method',
        'name' => 'method',
        'view' => 'pages/placeholder',
        'title_key' => 'page.method_title',
    ],
    [
        'method' => 'GET',
        'path' => '/about',
        'name' => 'about',
        'view' => 'pages/placeholder',
        'title_key' => 'page.about_title',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin routes
    |--------------------------------------------------------------------------
    */

    [
        'method' => 'GET',
        'path' => '/admin',
        'name' => 'admin.dashboard',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.dashboard',
    ],

    [
        'method' => 'GET',
        'path' => '/admin/entries',
        'name' => 'admin.entries.index',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.entries',
    ],
    [
        'method' => 'GET',
        'path' => '/admin/entry/create',
        'name' => 'admin.entries.create',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.entries',
    ],
    [
        'method' => 'POST',
        'path' => '/admin/entry/store',
        'name' => 'admin.entries.store',
    ],
    [
        'method' => 'GET',
        'path' => '/admin/entry/{id}/edit',
        'name' => 'admin.entries.edit',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.entries',
    ],
    [
        'method' => 'POST',
        'path' => '/admin/entry/{id}/update',
        'name' => 'admin.entries.update',
    ],
    [
        'method' => 'POST',
        'path' => '/admin/entry/{id}/delete',
        'name' => 'admin.entries.delete',
    ],

    [
        'method' => 'GET',
        'path' => '/admin/projects',
        'name' => 'admin.projects.index',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.projects',
    ],
    [
        'method' => 'GET',
        'path' => '/admin/project/create',
        'name' => 'admin.projects.create',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.projects',
    ],
    [
        'method' => 'POST',
        'path' => '/admin/project/store',
        'name' => 'admin.projects.store',
    ],
    [
        'method' => 'GET',
        'path' => '/admin/project/{id}/edit',
        'name' => 'admin.projects.edit',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.projects',
    ],
    [
        'method' => 'POST',
        'path' => '/admin/project/{id}/update',
        'name' => 'admin.projects.update',
    ],

    [
        'method' => 'GET',
        'path' => '/admin/reflections',
        'name' => 'admin.reflections.index',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.reflections',
    ],
    [
        'method' => 'POST',
        'path' => '/admin/reflection/{id}/approve',
        'name' => 'admin.reflections.approve',
    ],
    [
        'method' => 'POST',
        'path' => '/admin/reflection/{id}/reject',
        'name' => 'admin.reflections.reject',
    ],

    [
        'method' => 'GET',
        'path' => '/admin/settings',
        'name' => 'admin.settings',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.settings',
    ],
];
