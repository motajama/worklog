<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\View;
use App\Services\AppSettings;

class SettingsController
{
    public static function edit(): void
    {
        View::render('admin/settings/form', [
            'page_title' => t('page.admin_settings_title'),
            'settings' => [
                'home_intro_html_cs' => AppSettings::get(
                    'home_intro_html_cs',
                    '<h2>Worklog</h2><p>Tato aplikace je soukromá. Veřejný log najdeš na <a href="log.php">log.php</a>.</p>'
                ),
                'home_intro_html_en' => AppSettings::get(
                    'home_intro_html_en',
                    '<h2>Worklog</h2><p>This application is private. Public log lives in <a href="log.php">log.php</a>.</p>'
                ),
            ],
        ]);
    }

    public static function update(): void
    {
        $items = [
            'home_intro_html_cs' => (string) ($_POST['home_intro_html_cs'] ?? ''),
            'home_intro_html_en' => (string) ($_POST['home_intro_html_en'] ?? ''),
        ];

        AppSettings::setMany($items);

        flash('success', 'Nastavení bylo uloženo.');
        redirect(route_url('admin.settings'));
    }

    public static function changePassword(): void
    {
        $userId = Auth::id();

        if (!$userId) {
            flash('error', 'Nejsi přihlášen.');
            redirect(route_url('auth.login'));
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $newPasswordConfirm === '') {
            flash('error', 'Vyplň všechna heslová pole.');
            redirect(route_url('admin.settings'));
        }

        if ($newPassword !== $newPasswordConfirm) {
            flash('error', 'Nová hesla se neshodují.');
            redirect(route_url('admin.settings'));
        }

        if (mb_strlen($newPassword) < 10) {
            flash('error', 'Nové heslo musí mít alespoň 10 znaků.');
            redirect(route_url('admin.settings'));
        }

        $user = DB::selectOne(
            'SELECT id, password_hash
             FROM users
             WHERE id = :id
             LIMIT 1',
            ['id' => $userId]
        );

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            flash('error', 'Současné heslo není správné.');
            redirect(route_url('admin.settings'));
        }

        DB::execute(
            'UPDATE users
             SET password_hash = :hash,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id',
            [
                'id' => $userId,
                'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            ]
        );

        flash('success', 'Heslo bylo změněno.');
        redirect(route_url('admin.settings'));
    }
}
