<?php

namespace TurboFrame\Database;

class QueryBuilder
{
    private Connection $connection;
    private string $table;
    private array $select = ['*'];
    private array $where = [];
    private array $orderBy = [];
    private array $groupBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $joins = [];
    private array $bindings = [];

    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function select(string|array $columns): self
    {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = ['AND', $column, $operator, $value];
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = ['OR', $column, $operator, $value];
        $this->bindings[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->where[] = ['AND', $column, 'IN', "({$placeholders})"];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->where[] = ['AND', $column, 'IS', 'NULL'];
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->where[] = ['AND', $column, 'IS NOT', 'NULL'];
        return $this;
    }

    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->where[] = ['AND', $column, 'BETWEEN', '? AND ?'];
        $this->bindings[] = $min;
        $this->bindings[] = $max;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "{$column} " . strtoupper($direction);
        return $this;
    }

    public function groupBy(string|array $columns): self
    {
        $this->groupBy = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "INNER JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "LEFT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "RIGHT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function get(): array
    {
        return $this->connection->query($this->toSql(), $this->bindings);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function find(int|string $id): ?array
    {
        return $this->where('id', $id)->first();
    }

    public function count(): int
    {
        $this->select = ['COUNT(*) as aggregate'];
        $result = $this->first();
        return (int) ($result['aggregate'] ?? 0);
    }

    public function sum(string $column): float
    {
        $this->select = ["SUM({$column}) as aggregate"];
        $result = $this->first();
        return (float) ($result['aggregate'] ?? 0);
    }

    public function avg(string $column): float
    {
        $this->select = ["AVG({$column}) as aggregate"];
        $result = $this->first();
        return (float) ($result['aggregate'] ?? 0);
    }

    public function max(string $column): mixed
    {
        $this->select = ["MAX({$column}) as aggregate"];
        $result = $this->first();
        return $result['aggregate'] ?? null;
    }

    public function min(string $column): mixed
    {
        $this->select = ["MIN({$column}) as aggregate"];
        $result = $this->first();
        return $result['aggregate'] ?? null;
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function insert(array $data): int|string
    {
        return $this->connection->insert($this->table, $data);
    }

    public function update(array $data): int
    {
        $whereData = [];
        foreach ($this->where as $condition) {
            if ($condition[2] === '=') {
                $whereData[$condition[1]] = $condition[3];
            }
        }
        return $this->connection->update($this->table, $data, $whereData);
    }

    public function delete(): int
    {
        $whereData = [];
        foreach ($this->where as $condition) {
            if ($condition[2] === '=') {
                $whereData[$condition[1]] = $condition[3];
            }
        }
        return $this->connection->delete($this->table, $whereData);
    }

    public function toSql(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->select);
        $sql .= ' FROM ' . $this->table;

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->where)) {
            $sql .= ' WHERE';
            foreach ($this->where as $i => $condition) {
                [$boolean, $column, $operator, $value] = $condition;
                
                if ($i === 0) {
                    $boolean = '';
                }
                
                if (in_array($operator, ['IS', 'IS NOT', 'IN', 'BETWEEN'])) {
                    $sql .= " {$boolean} {$column} {$operator} {$value}";
                } else {
                    $sql .= " {$boolean} {$column} {$operator} ?";
                }
            }
        }

        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}
