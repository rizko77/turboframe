<?php

namespace TurboFrame\Database;

use TurboFrame\Core\Application;
use TurboFrame\Database\Schema\Blueprint;

class Schema
{
    private Connection $connection;

    public function __construct()
    {
        $this->connection = Application::getInstance()->make(Connection::class);
    }

    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        
        $sql = $blueprint->toCreateSql();
        $this->connection->raw($sql);
    }

    public function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, true);
        $callback($blueprint);
        
        $statements = $blueprint->toAlterSql();
        foreach ($statements as $sql) {
            $this->connection->raw($sql);
        }
    }

    public function drop(string $table): void
    {
        $this->connection->raw("DROP TABLE {$table}");
    }

    public function dropIfExists(string $table): void
    {
        $this->connection->raw("DROP TABLE IF EXISTS {$table}");
    }

    public function rename(string $from, string $to): void
    {
        $this->connection->raw("ALTER TABLE {$from} RENAME TO {$to}");
    }

    public function hasTable(string $table): bool
    {
        $result = $this->connection->query(
            "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_name = ?",
            [$table]
        );
        return ($result[0]['count'] ?? 0) > 0;
    }

    public function hasColumn(string $table, string $column): bool
    {
        $result = $this->connection->query(
            "SELECT COUNT(*) as count FROM information_schema.columns WHERE table_name = ? AND column_name = ?",
            [$table, $column]
        );
        return ($result[0]['count'] ?? 0) > 0;
    }
}
