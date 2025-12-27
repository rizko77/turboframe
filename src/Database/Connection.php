<?php

namespace TurboFrame\Database;

use PDO;
use PDOException;
use TurboFrame\Log\Logger;

class Connection
{
    private ?PDO $pdo = null;
    private array $config;
    private static array $drivers = [
        'mysql' => 'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        'pgsql' => 'pgsql:host=%s;port=%s;dbname=%s',
        'sqlite' => 'sqlite:%s',
    ];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connect(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $driver = $this->config['driver'] ?? 'mysql';

        if (!isset(self::$drivers[$driver])) {
            throw new PDOException("Unsupported database driver: {$driver}");
        }

        try {
            $dsn = $this->buildDsn($driver);
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
            ];

            if ($driver === 'sqlite') {
                $this->pdo = new PDO($dsn, null, null, $options);
            } else {
                $this->pdo = new PDO(
                    $dsn,
                    $this->config['username'] ?? 'root',
                    $this->config['password'] ?? '',
                    $options
                );
            }

            return $this->pdo;
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    private function buildDsn(string $driver): string
    {
        if ($driver === 'sqlite') {
            $database = $this->config['database'] ?? ':memory:';
            return sprintf(self::$drivers[$driver], $database);
        }

        return sprintf(
            self::$drivers[$driver],
            $this->config['host'] ?? '127.0.0.1',
            $this->config['port'] ?? ($driver === 'pgsql' ? '5432' : '3306'),
            $this->config['database'] ?? 'turboframe',
            $this->config['charset'] ?? 'utf8mb4'
        );
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function insert(string $table, array $data): int|string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->connect()->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "{$column} = ?";
        }
        
        $whereClauses = [];
        foreach (array_keys($where) as $column) {
            $whereClauses[] = "{$column} = ?";
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE " . implode(' AND ', $whereClauses);
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute(array_merge(array_values($data), array_values($where)));
        
        return $stmt->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        $whereClauses = [];
        foreach (array_keys($where) as $column) {
            $whereClauses[] = "{$column} = ?";
        }
        
        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClauses);
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute(array_values($where));
        
        return $stmt->rowCount();
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    public function raw(string $sql): int
    {
        return $this->connect()->exec($sql);
    }

    public function beginTransaction(): void
    {
        $this->connect()->beginTransaction();
    }

    public function commit(): void
    {
        $this->connect()->commit();
    }

    public function rollback(): void
    {
        $this->connect()->rollBack();
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getPdo(): PDO
    {
        return $this->connect();
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }
}
