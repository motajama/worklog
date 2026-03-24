<?php

use App\Controllers\AuthController;
use App\Controllers\ProjectController;

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
        'path' => '/about',
        'name' => 'about',
        'view' => 'pages/placeholder',
        'title_key' => 'page.about_title',
    ],
    [
        'method' => 'GET',
        'path' => '/method',
        'name' => 'method',
        'view' => 'pages/placeholder',
        'title_key' => 'page.method_title',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public reflection endpoint
    |--------------------------------------------------------------------------
    */

    [
        'method' => 'POST',
        'path' => '/reflection/store',
        'name' => 'reflections.store',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth routes
    |--------------------------------------------------------------------------
    */

    [
        'method' => 'GET',
        'path' => '/login',
        'name' => 'auth.login',
        'view' => 'auth/login',
        'title_key' => 'page.login_title',
        'middleware' => ['guest'],
    ],
    [
        'method' => 'POST',
        'path' => '/login',
        'name' => 'auth.login.submit',
        'handler' => [AuthController::class, 'login'],
        'middleware' => ['guest'],
    ],
    [
        'method' => 'POST',
        'path' => '/logout',
        'name' => 'auth.logout',
        'handler' => [AuthController::class, 'logout'],
        'middleware' => ['auth'],
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
        'middleware' => ['auth'],
    ],

    [
        'method' => 'GET',
        'path' => '/admin/entries',
        'name' => 'admin.entries.index',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.entries',
        'middleware' => ['auth'],
    ],
    [
        'method' => 'GET',
        'path' => '/admin/entry/create',
        'name' => 'admin.entries.create',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.entries',
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/entry/store',
        'name' => 'admin.entries.store',
        'middleware' => ['auth'],
    ],
    [
        'method' => 'GET',
        'path' => '/admin/entry/{id}/edit',
        'name' => 'admin.entries.edit',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.entries',
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/entry/{id}/update',
        'name' => 'admin.entries.update',
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/entry/{id}/delete',
        'name' => 'admin.entries.delete',
        'middleware' => ['auth'],
    ],

    [
        'method' => 'GET',
        'path' => '/admin/projects',
        'name' => 'admin.projects.index',
        'handler' => [ProjectController::class, 'index'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'GET',
        'path' => '/admin/project/create',
        'name' => 'admin.projects.create',
        'handler' => [ProjectController::class, 'create'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/project/store',
        'name' => 'admin.projects.store',
        'handler' => [ProjectController::class, 'store'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'GET',
        'path' => '/admin/project/{id}/edit',
        'name' => 'admin.projects.edit',
        'handler' => [ProjectController::class, 'edit'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/project/{id}/update',
        'name' => 'admin.projects.update',
        'handler' => [ProjectController::class, 'update'],
        'middleware' => ['auth'],
    ],

    [
        'method' => 'GET',
        'path' => '/admin/reflections',
        'name' => 'admin.reflections.index',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.reflections',
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/reflection/{id}/approve',
        'name' => 'admin.reflections.approve',
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/reflection/{id}/reject',
        'name' => 'admin.reflections.reject',
        'middleware' => ['auth'],
    ],

    [
        'method' => 'GET',
        'path' => '/admin/settings',
        'name' => 'admin.settings',
        'view' => 'admin/placeholder',
        'title_key' => 'nav.settings',
        'middleware' => ['auth'],
    ],
];
