<?php

final class AppProject
{
    public const MAX_PER_USER_DEFAULT = 5;

    public static function countForUser(int $userId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM apps WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function allForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM apps WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM apps WHERE id = ?');
        $stmt->execute([$id]);
        $app = $stmt->fetch();
        return $app ?: null;
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM apps WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $app = $stmt->fetch();
        return $app ?: null;
    }

    public static function packageIdExists(string $packageId): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM apps WHERE package_id = ?');
        $stmt->execute([$packageId]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public static function create(int $userId, array $fields): array
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO apps
                (user_id, name, package_id, target_url, icon_path, header_color, splash_bg_color,
                 splash_text_color, splash_text, font_name, version_code, version_name,
                 key_alias, key_password, store_password, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $userId,
            $fields['name'],
            $fields['package_id'],
            $fields['target_url'],
            $fields['icon_path'] ?? null,
            $fields['header_color'],
            $fields['splash_bg_color'],
            $fields['splash_text_color'],
            $fields['splash_text'],
            $fields['font_name'],
            1,
            $fields['version_name'] ?? '1.0.0',
            bin2hex(random_bytes(6)),
            bin2hex(random_bytes(12)),
            bin2hex(random_bytes(12)),
            'draft',
        ]);

        return self::find((int) Database::connection()->lastInsertId());
    }

    public static function update(int $id, array $fields): void
    {
        $columns = [];
        $values = [];
        foreach (['name', 'target_url', 'icon_path', 'header_color', 'splash_bg_color', 'splash_text_color', 'splash_text', 'font_name'] as $column) {
            if (array_key_exists($column, $fields)) {
                $columns[] = "{$column} = ?";
                $values[] = $fields[$column];
            }
        }

        if (empty($columns)) {
            return;
        }

        $values[] = $id;
        $sql = 'UPDATE apps SET ' . implode(', ', $columns) . ' WHERE id = ?';
        Database::connection()->prepare($sql)->execute($values);
    }

    public static function markStatus(int $id, string $status): void
    {
        $stmt = Database::connection()->prepare('UPDATE apps SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function bumpVersion(int $id): array
    {
        $app = self::find($id);
        $newVersionCode = $app['version_code'] + 1;
        $parts = explode('.', $app['version_name']);
        $parts[count($parts) - 1] = ((int) end($parts)) + 1;
        $newVersionName = implode('.', $parts);

        $stmt = Database::connection()->prepare('UPDATE apps SET version_code = ?, version_name = ?, status = ? WHERE id = ?');
        $stmt->execute([$newVersionCode, $newVersionName, 'queued', $id]);

        return self::find($id);
    }

    public static function saveKeystore(int $id, string $keystoreBase64): void
    {
        $stmt = Database::connection()->prepare('UPDATE apps SET keystore_base64 = ? WHERE id = ?');
        $stmt->execute([$keystoreBase64, $id]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM apps WHERE id = ?')->execute([$id]);
    }

    public static function suggestPackageId(string $appName): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '', $appName));
        $slug = $slug === '' ? 'app' : $slug;
        return 'com.appforge.' . $slug . substr(bin2hex(random_bytes(3)), 0, 4);
    }
}
