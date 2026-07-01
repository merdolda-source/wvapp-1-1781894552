<?php

final class DownloadLog
{
    public static function log(int $appId, string $type): void
    {
        Database::connection()->prepare('INSERT INTO download_logs (app_id, type) VALUES (?, ?)')->execute([$appId, $type]);
    }

    /** @return array<int, array{day: string, total: int}> Daily counts for the last $days days. */
    public static function dailyForUser(int $userId, int $days = 30): array
    {
        $sql = 'SELECT DATE(dl.created_at) AS day, COUNT(*) AS total
                FROM download_logs dl
                INNER JOIN apps a ON a.id = dl.app_id
                WHERE a.user_id = ? AND dl.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(dl.created_at)
                ORDER BY day ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array{day: string, total: int}> */
    public static function dailyGlobal(int $days = 30): array
    {
        $sql = 'SELECT DATE(created_at) AS day, COUNT(*) AS total
                FROM download_logs
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY day ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public static function totalForUser(int $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM download_logs dl INNER JOIN apps a ON a.id = dl.app_id WHERE a.user_id = ?';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function totalGlobal(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM download_logs')->fetchColumn();
    }

    public static function totalForApp(int $appId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM download_logs WHERE app_id = ?');
        $stmt->execute([$appId]);
        return (int) $stmt->fetchColumn();
    }
}
