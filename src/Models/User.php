<?php

final class User
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByGoogleId(string $googleId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE google_id = ?');
        $stmt->execute([$googleId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $name, string $email, ?string $password, ?string $googleId = null, ?string $avatar = null): array
    {
        $hash = $password !== null ? password_hash($password, PASSWORD_DEFAULT) : null;

        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, google_id, avatar_url) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $hash, $googleId, $avatar]);

        return self::find((int) Database::connection()->lastInsertId());
    }

    public static function linkGoogleAccount(int $userId, string $googleId, ?string $avatar): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET google_id = ?, avatar_url = COALESCE(?, avatar_url) WHERE id = ?');
        $stmt->execute([$googleId, $avatar, $userId]);
    }

    public static function verifyPassword(array $user, string $password): bool
    {
        return $user['password_hash'] !== null && password_verify($password, $user['password_hash']);
    }

    public static function updateName(int $id, string $name): void
    {
        Database::connection()->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $id]);
    }

    public static function updatePassword(int $id, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        Database::connection()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $id]);
    }

    public static function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public static function allWithAppCounts(): array
    {
        $sql = 'SELECT u.*, COUNT(a.id) AS app_count
                FROM users u
                LEFT JOIN apps a ON a.user_id = u.id
                GROUP BY u.id
                ORDER BY u.created_at DESC';

        return Database::connection()->query($sql)->fetchAll();
    }
}
