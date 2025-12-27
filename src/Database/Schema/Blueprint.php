<?php

namespace TurboFrame\Database\Schema;

class Blueprint
{
    private string $table;
    private bool $alter;
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];
    private ?string $engine = 'InnoDB';
    private ?string $charset = 'utf8mb4';
    private ?string $collation = 'utf8mb4_unicode_ci';

    public function __construct(string $table, bool $alter = false)
    {
        $this->table = $table;
        $this->alter = $alter;
    }

    public function id(string $name = 'id'): Column
    {
        return $this->bigInteger($name)->unsigned()->autoIncrement()->primary();
    }

    public function bigInteger(string $name): Column
    {
        return $this->addColumn('bigint', $name);
    }

    public function integer(string $name): Column
    {
        return $this->addColumn('int', $name);
    }

    public function smallInteger(string $name): Column
    {
        return $this->addColumn('smallint', $name);
    }

    public function tinyInteger(string $name): Column
    {
        return $this->addColumn('tinyint', $name);
    }

    public function float(string $name, int $precision = 8, int $scale = 2): Column
    {
        return $this->addColumn("float({$precision},{$scale})", $name);
    }

    public function double(string $name, int $precision = 16, int $scale = 4): Column
    {
        return $this->addColumn("double({$precision},{$scale})", $name);
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2): Column
    {
        return $this->addColumn("decimal({$precision},{$scale})", $name);
    }

    public function string(string $name, int $length = 255): Column
    {
        return $this->addColumn("varchar({$length})", $name);
    }

    public function text(string $name): Column
    {
        return $this->addColumn('text', $name);
    }

    public function mediumText(string $name): Column
    {
        return $this->addColumn('mediumtext', $name);
    }

    public function longText(string $name): Column
    {
        return $this->addColumn('longtext', $name);
    }

    public function boolean(string $name): Column
    {
        return $this->addColumn('tinyint(1)', $name);
    }

    public function date(string $name): Column
    {
        return $this->addColumn('date', $name);
    }

    public function datetime(string $name): Column
    {
        return $this->addColumn('datetime', $name);
    }

    public function timestamp(string $name): Column
    {
        return $this->addColumn('timestamp', $name);
    }

    public function time(string $name): Column
    {
        return $this->addColumn('time', $name);
    }

    public function json(string $name): Column
    {
        return $this->addColumn('json', $name);
    }

    public function binary(string $name): Column
    {
        return $this->addColumn('blob', $name);
    }

    public function uuid(string $name): Column
    {
        return $this->string($name, 36);
    }

    public function enum(string $name, array $values): Column
    {
        $escaped = array_map(fn($v) => "'{$v}'", $values);
        return $this->addColumn('enum(' . implode(',', $escaped) . ')', $name);
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable()->useCurrent();
        $this->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
    }

    public function softDeletes(): Column
    {
        return $this->timestamp('deleted_at')->nullable();
    }

    public function foreignId(string $name): Column
    {
        return $this->bigInteger($name)->unsigned();
    }

    private function addColumn(string $type, string $name): Column
    {
        $column = new Column($name, $type);
        $this->columns[] = $column;
        return $column;
    }

    public function index(string|array $columns, ?string $name = null): self
    {
        $columns = (array) $columns;
        $name = $name ?? $this->table . '_' . implode('_', $columns) . '_index';
        $this->indexes[] = ['type' => 'INDEX', 'name' => $name, 'columns' => $columns];
        return $this;
    }

    public function unique(string|array $columns, ?string $name = null): self
    {
        $columns = (array) $columns;
        $name = $name ?? $this->table . '_' . implode('_', $columns) . '_unique';
        $this->indexes[] = ['type' => 'UNIQUE', 'name' => $name, 'columns' => $columns];
        return $this;
    }

    public function foreign(string $column): ForeignKey
    {
        $fk = new ForeignKey($this->table, $column);
        $this->foreignKeys[] = $fk;
        return $fk;
    }

    public function dropColumn(string $column): void
    {
        $this->columns[] = ['drop' => $column];
    }

    public function engine(string $engine): self
    {
        $this->engine = $engine;
        return $this;
    }

    public function toCreateSql(): string
    {
        $sql = "CREATE TABLE {$this->table} (\n";
        
        $definitions = [];
        $primaryKey = null;

        foreach ($this->columns as $column) {
            if ($column instanceof Column) {
                $definitions[] = '  ' . $column->toSql();
                if ($column->isPrimary()) {
                    $primaryKey = $column->getName();
                }
            }
        }

        if ($primaryKey) {
            $definitions[] = "  PRIMARY KEY ({$primaryKey})";
        }

        foreach ($this->indexes as $index) {
            $cols = implode(', ', $index['columns']);
            $definitions[] = "  {$index['type']} {$index['name']} ({$cols})";
        }

        foreach ($this->foreignKeys as $fk) {
            $definitions[] = '  ' . $fk->toSql();
        }

        $sql .= implode(",\n", $definitions);
        $sql .= "\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation}";

        return $sql;
    }

    public function toAlterSql(): array
    {
        $statements = [];

        foreach ($this->columns as $column) {
            if (is_array($column) && isset($column['drop'])) {
                $statements[] = "ALTER TABLE {$this->table} DROP COLUMN {$column['drop']}";
            } elseif ($column instanceof Column) {
                $statements[] = "ALTER TABLE {$this->table} ADD COLUMN " . $column->toSql();
            }
        }

        return $statements;
    }
}
