<?php

namespace TurboFrame\Database\Schema;

class Column
{
    private string $name;
    private string $type;
    private bool $nullable = false;
    private mixed $default = null;
    private bool $hasDefault = false;
    private bool $unsigned = false;
    private bool $autoIncrement = false;
    private bool $primary = false;
    private bool $unique = false;
    private bool $useCurrent = false;
    private bool $useCurrentOnUpdate = false;
    private ?string $after = null;
    private ?string $comment = null;

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;
        $this->hasDefault = true;
        return $this;
    }

    public function unsigned(): self
    {
        $this->unsigned = true;
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->autoIncrement = true;
        return $this;
    }

    public function primary(): self
    {
        $this->primary = true;
        return $this;
    }

    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }

    public function useCurrent(): self
    {
        $this->useCurrent = true;
        return $this;
    }

    public function useCurrentOnUpdate(): self
    {
        $this->useCurrentOnUpdate = true;
        return $this;
    }

    public function after(string $column): self
    {
        $this->after = $column;
        return $this;
    }

    public function comment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function toSql(): string
    {
        $sql = "`{$this->name}` {$this->type}";

        if ($this->unsigned) {
            $sql .= ' UNSIGNED';
        }

        if ($this->nullable) {
            $sql .= ' NULL';
        } else {
            $sql .= ' NOT NULL';
        }

        if ($this->autoIncrement) {
            $sql .= ' AUTO_INCREMENT';
        }

        if ($this->useCurrent) {
            $sql .= ' DEFAULT CURRENT_TIMESTAMP';
        } elseif ($this->hasDefault) {
            if ($this->default === null) {
                $sql .= ' DEFAULT NULL';
            } elseif (is_bool($this->default)) {
                $sql .= ' DEFAULT ' . ($this->default ? '1' : '0');
            } elseif (is_numeric($this->default)) {
                $sql .= " DEFAULT {$this->default}";
            } else {
                $sql .= " DEFAULT '{$this->default}'";
            }
        }

        if ($this->useCurrentOnUpdate) {
            $sql .= ' ON UPDATE CURRENT_TIMESTAMP';
        }

        if ($this->unique && !$this->primary) {
            $sql .= ' UNIQUE';
        }

        if ($this->comment) {
            $sql .= " COMMENT '{$this->comment}'";
        }

        if ($this->after) {
            $sql .= " AFTER `{$this->after}`";
        }

        return $sql;
    }
}
