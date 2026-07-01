<?php

/**
 * Logs one row every time an installed app calls /api/config on launch, used
 * to show how actively each user's apps are being opened by their end users.
 */
final class UsageLog
{
    public static function log(int $appId): void
    {
        Database::connection()->prepare('INSERT INTO usage_logs (app_id) VALUES (?)')->execute([$appId]);
    }

    /** @return array<int, array{month: string, total: int}> Monthly counts for the last $months months. */
    public static function monthlyForUser(int $userId, int $months = 12): array
    {
        $sql = "SELECT DATE_FORMAT(ul.created_at, '%Y-%m') AS month, COUNT(*) AS total
                FROM usage_logs ul
                INNER JOIN apps a ON a.id = ul.app_id
                WHERE a.user_id = ? AND ul.created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(ul.created_at, '%Y-%m')
                ORDER BY month ASC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$userId, $months]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array{month: string, total: int}> */
    public static function monthlyGlobal(int $months = 12): array
    {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
                FROM usage_logs
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$months]);
        return $stmt->fetchAll();
    }

    public static function totalForUser(int $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM usage_logs ul INNER JOIN apps a ON a.id = ul.app_id WHERE a.user_id = ?';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function totalGlobal(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM usage_logs')->fetchColumn();
    }

    public static function todayGlobal(): int
    {
        $stmt = Database::connection()->query('SELECT COUNT(*) FROM usage_logs WHERE DATE(created_at) = CURDATE()');
        return (int) $stmt->fetchColumn();
    }

    public static function totalForApp(int $appId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM usage_logs WHERE app_id = ?');
        $stmt->execute([$appId]);
        return (int) $stmt->fetchColumn();
    }
}
