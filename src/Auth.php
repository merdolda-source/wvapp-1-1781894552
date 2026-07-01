<?php

final class Auth
{
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $cached = User::find((int) $_SESSION['user_id']);
        return $cached;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    public static function isAdmin(): bool
    {
        $adminEmail = trim((string) Config::get('ADMIN_EMAIL'));
        if ($adminEmail === '') {
            return false;
        }

        $user = self::user();
        return $user !== null && strcasecmp(trim($user['email']), $adminEmail) === 0;
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(404);
            View::render('errors/404', []);
            exit;
        }
    }
}
