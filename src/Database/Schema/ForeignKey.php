<?php

namespace TurboFrame\Database\Schema;

class ForeignKey
{
    private string $table;
    private string $column;
    private string $referencesTable = '';
    private string $referencesColumn = '';
    private string $onDelete = 'RESTRICT';
    private string $onUpdate = 'RESTRICT';

    public function __construct(string $table, string $column)
    {
        $this->table = $table;
        $this->column = $column;
    }

    public function references(string $column): self
    {
        $this->referencesColumn = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->referencesTable = $table;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = strtoupper($action);
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        return $this->onDelete('CASCADE');
    }

    public function cascadeOnUpdate(): self
    {
        return $this->onUpdate('CASCADE');
    }

    public function nullOnDelete(): self
    {
        return $this->onDelete('SET NULL');
    }

    public function toSql(): string
    {
        $name = "{$this->table}_{$this->column}_foreign";
        
        return "CONSTRAINT {$name} FOREIGN KEY ({$this->column}) " .
               "REFERENCES {$this->referencesTable}({$this->referencesColumn}) " .
               "ON DELETE {$this->onDelete} ON UPDATE {$this->onUpdate}";
    }
}
