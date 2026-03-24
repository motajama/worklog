<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class DB
{
    protected static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $default = config('database.default', 'sqlite');
        $config = config('database.connections.' . $default);

        if (!$config || !is_array($config)) {
            throw new RuntimeException('Database configuration is missing.');
        }

        try {
            if ($default === 'sqlite') {
                $databasePath = $config['database'] ?? base_path('database/worklog.sqlite');

                $dir = dirname($databasePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                if (!file_exists($databasePath)) {
                    touch($databasePath);
                }

                $dsn = 'sqlite:' . $databasePath;

                self::$pdo = new PDO($dsn, null, null, self::sqliteOptions());
                self::$pdo->exec('PRAGMA foreign_keys = ON');
            } else {
                $host = $config['host'] ?? '127.0.0.1';
                $port = $config['port'] ?? '3306';
                $database = $config['database'] ?? '';
                $charset = $config['charset'] ?? 'utf8mb4';
                $username = $config['username'] ?? '';
                $password = $config['password'] ?? '';
                $timeout = (int) ($config['timeout'] ?? 5);

                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

                self::$pdo = new PDO($dsn, $username, $password, self::mysqlOptions($timeout));
            }
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }

        return self::$pdo;
    }

    public static function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function selectAll(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = self::connection()->prepare($sql);
        return $stmt->execute($params);
    }

    protected static function baseOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    protected static function sqliteOptions(): array
    {
        return self::baseOptions();
    }

    protected static function mysqlOptions(int $timeout = 5): array
    {
        return self::baseOptions() + [
            PDO::ATTR_TIMEOUT => $timeout,
        ];
    }
}
