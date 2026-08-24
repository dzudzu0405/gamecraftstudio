<?php
namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * A thin wrapper around PDO. Supports MySQL (production on cPanel) and SQLite
 * (local testing). Every query goes through a prepared statement, which is what
 * keeps SQL injection out.
 */
class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    public static function connect(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = Config::get('database', []);
        self::$driver = $cfg['driver'] ?? 'mysql';

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            if (self::$driver === 'sqlite') {
                $path = $cfg['sqlite_path'] ?? dirname(__DIR__, 2) . '/storage/gamecraft.sqlite';
                @mkdir(dirname($path), 0775, true);
                self::$pdo = new PDO('sqlite:' . $path, null, null, $options);
                self::$pdo->exec('PRAGMA foreign_keys = ON');
                self::$pdo->exec('PRAGMA journal_mode = WAL');
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $cfg['host'] ?? 'localhost',
                    (int)($cfg['port'] ?? 3306),
                    $cfg['name'] ?? '',
                    $cfg['charset'] ?? 'utf8mb4'
                );
                self::$pdo = new PDO($dsn, $cfg['user'] ?? '', $cfg['pass'] ?? '', $options);
            }
        } catch (PDOException $e) {
            throw new \RuntimeException(
                'Could not connect to the database. Please check the details in config.php. Reason: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return self::$pdo;
    }

    public static function driver(): string
    {
        self::connect();
        return self::$driver;
    }

    public static function isConnected(): bool
    {
        try {
            self::connect();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch every row */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Fetch one row, or null if there is none */
    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Fetch a single value from the first column */
    public static function value(string $sql, array $params = [])
    {
        $row = self::run($sql, $params)->fetch(PDO::FETCH_NUM);
        return $row === false ? null : $row[0];
    }

    public static function count(string $sql, array $params = []): int
    {
        return (int) self::value($sql, $params);
    }

    /** Insert one row and return the new id */
    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::quote($table),
            implode(', ', array_map([self::class, 'quote'], $cols)),
            implode(', ', array_map(fn($c) => ':' . $c, $cols))
        );
        self::run($sql, $data);
        return (int) self::connect()->lastInsertId();
    }

    /** Update rows matching a where clause given as column => value */
    public static function update(string $table, array $data, array $where): int
    {
        $set = [];
        $params = [];
        foreach ($data as $col => $val) {
            $set[] = self::quote($col) . ' = :set_' . $col;
            $params['set_' . $col] = $val;
        }
        $cond = [];
        foreach ($where as $col => $val) {
            $cond[] = self::quote($col) . ' = :where_' . $col;
            $params['where_' . $col] = $val;
        }
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            self::quote($table),
            implode(', ', $set),
            implode(' AND ', $cond)
        );
        return self::run($sql, $params)->rowCount();
    }

    public static function delete(string $table, array $where): int
    {
        $cond = [];
        $params = [];
        foreach ($where as $col => $val) {
            $cond[] = self::quote($col) . ' = :' . $col;
            $params[$col] = $val;
        }
        $sql = sprintf('DELETE FROM %s WHERE %s', self::quote($table), implode(' AND ', $cond));
        return self::run($sql, $params)->rowCount();
    }

    public static function transaction(callable $fn)
    {
        $pdo = self::connect();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Does this table exist yet? Used to tell whether the app is installed */
    public static function tableExists(string $table): bool
    {
        try {
            self::run('SELECT 1 FROM ' . self::quote($table) . ' LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function quote(string $identifier): string
    {
        return '`' . str_replace('`', '', $identifier) . '`';
    }
}
