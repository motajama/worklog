<?php

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\EntryController;
use App\Controllers\HomeController;
use App\Controllers\ProjectController;
use App\Controllers\ReflectionController;
use App\Controllers\SettingsController;

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
        'handler' => [HomeController::class, 'index'],
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
        'handler' => [ReflectionController::class, 'store'],
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
        'handler' => [AdminController::class, 'dashboard'],
        'middleware' => ['auth'],
    ],

    [
        'method' => 'GET',
        'path' => '/admin/entries',
        'name' => 'admin.entries.index',
        'handler' => [EntryController::class, 'index'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'GET',
        'path' => '/admin/entry/create',
        'name' => 'admin.entries.create',
        'handler' => [EntryController::class, 'create'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/entry/store',
        'name' => 'admin.entries.store',
        'handler' => [EntryController::class, 'store'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'GET',
        'path' => '/admin/entry/{id}/edit',
        'name' => 'admin.entries.edit',
        'handler' => [EntryController::class, 'edit'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/entry/{id}/update',
        'name' => 'admin.entries.update',
        'handler' => [EntryController::class, 'update'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/entry/{id}/delete',
        'name' => 'admin.entries.delete',
        'handler' => [EntryController::class, 'delete'],
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
        'handler' => [ReflectionController::class, 'index'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/reflection/{id}/approve',
        'name' => 'admin.reflections.approve',
        'handler' => [ReflectionController::class, 'approve'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/reflection/{id}/reject',
        'name' => 'admin.reflections.reject',
        'handler' => [ReflectionController::class, 'reject'],
        'middleware' => ['auth'],
    ],

    [
        'method' => 'GET',
        'path' => '/admin/settings',
        'name' => 'admin.settings',
        'handler' => [SettingsController::class, 'edit'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/settings',
        'name' => 'admin.settings.update',
        'handler' => [SettingsController::class, 'update'],
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/admin/settings/password',
        'name' => 'admin.settings.password',
        'handler' => [SettingsController::class, 'changePassword'],
        'middleware' => ['auth'],
    ],
];
