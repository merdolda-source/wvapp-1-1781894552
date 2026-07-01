<?php

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $host = Config::get('DB_HOST', '127.0.0.1');
            $name = Config::get('DB_NAME', 'webviewbuilder');
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

            self::$pdo = new PDO($dsn, Config::get('DB_USER', 'root'), Config::get('DB_PASS', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$pdo;
    }
}
