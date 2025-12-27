<?php

namespace TurboFrame\Database;

use TurboFrame\Core\Application;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public static function query(): QueryBuilder
    {
        $instance = new static();
        $connection = Application::getInstance()->make(Connection::class);
        return new QueryBuilder($connection, $instance->getTable());
    }

    public static function all(): array
    {
        $results = static::query()->get();
        return array_map(fn($row) => (new static($row))->setExists(true), $results);
    }

    public static function find(int|string $id): ?static
    {
        $result = static::query()->find($id);
        if ($result === null) {
            return null;
        }
        return (new static($result))->setExists(true);
    }

    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);
        if ($model === null) {
            throw new \Exception("Model not found: {$id}");
        }
        return $model;
    }

    public static function where(string $column, mixed $operator = null, mixed $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (empty($this->fillable) || in_array($key, $this->fillable)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    public function save(): bool
    {
        $connection = Application::getInstance()->make(Connection::class);
        
        $data = [];
        foreach ($this->attributes as $key => $value) {
            if ($key !== $this->primaryKey || !$this->exists) {
                $data[$key] = $this->castForStorage($key, $value);
            }
        }

        if ($this->exists) {
            $affected = $connection->update(
                $this->getTable(),
                $data,
                [$this->primaryKey => $this->getKey()]
            );
            return $affected > 0;
        }

        $id = $connection->insert($this->getTable(), $data);
        $this->setAttribute($this->primaryKey, $id);
        $this->exists = true;
        return true;
    }

    public function update(array $attributes = []): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $connection = Application::getInstance()->make(Connection::class);
        $affected = $connection->delete(
            $this->getTable(),
            [$this->primaryKey => $this->getKey()]
        );

        $this->exists = false;
        return $affected > 0;
    }

    public function refresh(): self
    {
        $fresh = static::find($this->getKey());
        if ($fresh !== null) {
            $this->attributes = $fresh->attributes;
            $this->original = $this->attributes;
        }
        return $this;
    }

    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $this->castAttribute($key, $value);
        return $this;
    }

    public function getAttribute(string $key): mixed
    {
        $value = $this->attributes[$key] ?? null;
        return $this->castAttribute($key, $value);
    }

    private function castAttribute(string $key, mixed $value): mixed
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }

        return match($this->casts[$key]) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : $value,
            'datetime' => $value instanceof \DateTime ? $value : new \DateTime($value),
            default => $value,
        };
    }

    private function castForStorage(string $key, mixed $value): mixed
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }

        return match($this->casts[$key]) {
            'array', 'json' => is_array($value) ? json_encode($value) : $value,
            'datetime' => $value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : $value,
            default => $value,
        };
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    public function getTable(): string
    {
        if ($this->table) {
            return $this->table;
        }
        
        $className = (new \ReflectionClass($this))->getShortName();
        $table = preg_replace('/([a-z])([A-Z])/', '$1_$2', $className);
        return strtolower($table) . 's';
    }

    public function setExists(bool $exists): self
    {
        $this->exists = $exists;
        $this->original = $this->attributes;
        return $this;
    }

    public function isDirty(): bool
    {
        return $this->attributes !== $this->original;
    }

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }
}
