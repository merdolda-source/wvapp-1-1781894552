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
}
