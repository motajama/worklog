<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\AppSettings;

class HomeController
{
    public static function index(): void
    {
        if (Auth::check()) {
            redirect(route_url('admin.dashboard'));
        }

        $locale = current_locale();

        $fallback = $locale === 'en'
            ? '<h2>Worklog</h2><p>This application is private. Public log lives in <a href="log.php">log.php</a>.</p>'
            : '<h2>Worklog</h2><p>Tato aplikace je soukromá. Veřejný log najdeš na <a href="log.php">log.php</a>.</p>';

        $introHtml = AppSettings::localeValue('home_intro_html', $locale, $fallback);

        View::render('pages/intro', [
            'page_title' => config('app.app_name'),
            'intro_html' => $introHtml,
        ]);
    }
}
