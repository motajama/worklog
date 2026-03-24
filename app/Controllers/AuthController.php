<?php

namespace App\Controllers;

use App\Core\Auth;
use RuntimeException;

class AuthController
{
    public static function login(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            flash('error', 'Vyplň uživatelské jméno i heslo.');
            old_input(['username' => $username]);
            redirect(route_url('auth.login'));
        }

        try {
            if (!Auth::attempt($username, $password)) {
                flash('error', 'Přihlášení se nepovedlo.');
                old_input(['username' => $username]);
                redirect(route_url('auth.login'));
            }
        } catch (RuntimeException $e) {
            flash('error', 'Databáze není připravená nebo není dostupná.');
            old_input(['username' => $username]);
            redirect(route_url('auth.login'));
        }

        forget_old_input();

        $intended = $_SESSION['url.intended'] ?? route_url('admin.dashboard');
        unset($_SESSION['url.intended']);

        flash('success', 'Přihlášení proběhlo v pořádku.');
        redirect($intended);
    }

    public static function logout(): void
    {
        Auth::logout();
        flash('success', 'Byl jsi odhlášen.');
        redirect(route_url('home'));
    }
}
