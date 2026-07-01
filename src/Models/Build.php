<?php

final class Build
{
    public static function create(int $appId, string $buildToken, int $versionCode, string $versionName): array
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO builds (app_id, build_token, version_code, version_name, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$appId, $buildToken, $versionCode, $versionName, 'queued']);

        return self::find((int) Database::connection()->lastInsertId());
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM builds WHERE id = ?');
        $stmt->execute([$id]);
        $build = $stmt->fetch();
        return $build ?: null;
    }

    public static function findByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM builds WHERE build_token = ?');
        $stmt->execute([$token]);
        $build = $stmt->fetch();
        return $build ?: null;
    }

    public static function latestForApp(int $appId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM builds WHERE app_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$appId]);
        $build = $stmt->fetch();
        return $build ?: null;
    }

    public static function historyForApp(int $appId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM builds WHERE app_id = ? ORDER BY id DESC');
        $stmt->execute([$appId]);
        return $stmt->fetchAll();
    }

    public static function setRunId(int $id, int $runId): void
    {
        Database::connection()->prepare('UPDATE builds SET github_run_id = ?, status = ? WHERE id = ?')
            ->execute([$runId, 'building', $id]);
    }

    public static function markSuccess(int $id, string $apkPath, string $aabPath): void
    {
        Database::connection()->prepare('UPDATE builds SET status = ?, apk_path = ?, aab_path = ? WHERE id = ?')
            ->execute(['success', $apkPath, $aabPath, $id]);
    }

    public static function markFailed(int $id, ?string $logUrl = null): void
    {
        Database::connection()->prepare('UPDATE builds SET status = ?, log_url = ? WHERE id = ?')
            ->execute(['failed', $logUrl, $id]);
    }
}
