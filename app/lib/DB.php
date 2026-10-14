<?php
/**
 * Thin PDO wrapper that works with both MySQL (production) and SQLite (local/dev).
 */
final class DB
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    public static function connect(array $cfg): PDO
    {
        $driver = $cfg['driver'] ?? 'mysql';
        if ($driver === 'sqlite') {
            $path = $cfg['sqlite_path'] ?? (STORAGE . '/database.sqlite');
            if (!str_starts_with($path, '/') && !preg_match('~^[A-Za-z]:~', $path)) {
                $path = ROOT . '/' . $path;
            }
            $pdo = new PDO('sqlite:' . $path);
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $cfg['host'] ?? 'localhost',
                (int)($cfg['port'] ?? 3306),
                $cfg['name'] ?? ''
            );
            $pdo = new PDO($dsn, $cfg['user'] ?? '', $cfg['pass'] ?? '', [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+00:00'",
            ]);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        self::$pdo = $pdo;
        self::$driver = $driver;
        return $pdo;
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new RuntimeException('Database is not connected.');
        }
        return self::$pdo;
    }

    public static function connected(): bool
    {
        return self::$pdo !== null;
    }

    public static function driver(): string
    {
        return self::$driver;
    }

    public static function q(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute(array_values($params) === $params ? $params : $params);
        return $st;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::q($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::q($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function val(string $sql, array $params = [])
    {
        $v = self::q($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::ident($table),
            implode(', ', array_map([self::class, 'ident'], $cols)),
            implode(', ', array_fill(0, count($cols), '?'))
        );
        self::q($sql, array_values($data));
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        if (!$data) {
            return 0;
        }
        $set = implode(', ', array_map(fn($c) => self::ident($c) . ' = ?', array_keys($data)));
        $sql = sprintf('UPDATE %s SET %s WHERE %s', self::ident($table), $set, $where);
        return self::q($sql, array_merge(array_values($data), $params))->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::q(sprintf('DELETE FROM %s WHERE %s', self::ident($table), $where), $params)->rowCount();
    }

    public static function ident(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException('Invalid identifier: ' . $name);
        }
        return self::$driver === 'sqlite' ? '"' . $name . '"' : '`' . $name . '`';
    }

    public static function tx(callable $fn)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $r = $fn();
            $pdo->commit();
            return $r;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function tableExists(string $table): bool
    {
        try {
            if (self::$driver === 'sqlite') {
                return (bool)self::val("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
            }
            return (bool)self::val('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$table]);
        } catch (Throwable $e) {
            return false;
        }
    }
}
