<?php

namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['auth_user']) && is_array($_SESSION['auth_user']);
    }

    public static function user(): ?array
    {
        return self::check() ? $_SESSION['auth_user'] : null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        if (!$user || !isset($user['id'])) {
            return null;
        }

        return (int) $user['id'];
    }

    public static function attempt(string $username, string $password): bool
    {
        $user = DB::selectOne(
            'SELECT id, username, password_hash, role, is_active
             FROM users
             WHERE username = :username
             LIMIT 1',
            ['username' => $username]
        );

        if (!$user) {
            return false;
        }

        if ((int) ($user['is_active'] ?? 0) !== 1) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        self::login($user);

        DB::execute(
            'UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id',
            ['id' => $user['id']]
        );

        return true;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['auth_user'] = [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'role' => (string) ($user['role'] ?? 'admin'),
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_user']);
        unset($_SESSION['url.intended']);
        session_regenerate_id(true);
    }
}
