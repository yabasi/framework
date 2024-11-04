<?php

namespace Yabasi\Database;

use Exception;
use PDOException;
use Yabasi\Container\Container;
use Yabasi\Database\Relations\BelongsTo;
use Yabasi\Database\Relations\HasMany;
use Yabasi\Database\Relations\HasOne;
use Yabasi\Database\Relations\Relation;
use Yabasi\Logging\Logger;
use Yabasi\Support\Collection;


abstract class Model
{
    protected static Container $container;
    protected static Connection $connection;
    protected static Logger $logger;
    protected static string $table;
    protected static array $eagerLoad = [];

    protected array $attributes = [];
    protected array $original = [];
    protected array $casts = [];
    protected array $changes = [];
    protected array $relations = [];
    protected array $with = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    protected function getConnection(): Connection
    {
        return static::$connection;
    }

    protected function getLogger(): Logger
    {
        return static::$logger;
    }

    public static function setLogger(Logger $logger): void
    {
        static::$logger = $logger;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public static function setContainer(Container $container): void
    {
        static::$container = $container;
    }

    public static function setConnection(Connection $connection): void
    {
        static::$connection = $connection;
    }

    public static function getTable(): string
    {
        return static::$table;
    }

    /**
     * @return QueryBuilder<static>
     */
    public static function query(): QueryBuilder
    {
        return (new static)->newQuery()->from(static::getTable());
    }

    /**
     * @param int|string $id
     * @return static|null
     */
    public static function find($id): ?static
    {
        return static::query()->where('id', '=', $id)->first();
    }

    /**
     * @return static[]|Collection
     */
    public static function all(): array|Collection
    {
        return static::query()->get()->toArray();
    }

    public static function where($column, $operator = null, $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    public static function count(): int
    {
        return static::query()->count();
    }

    public static function with($relations): QueryBuilder
    {
        return static::query()->with(
            is_string($relations) ? func_get_args() : $relations
        );
    }

    protected function cast($value, $type)
    {
        switch ($type) {
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
                return (bool) $value;
            default:
                return $value;
        }
    }

    public static function latest(string $column = 'created_at'): QueryBuilder
    {
        return static::query()->orderBy($column, 'desc');
    }

    public static function oldest(string $column = 'created_at'): QueryBuilder
    {
        return static::query()->orderBy($column, 'asc');
    }

    public static function orderByDesc(string $column): QueryBuilder
    {
        return static::query()->orderBy($column, 'desc');
    }

    public static function orderByAsc(string $column): QueryBuilder
    {
        return static::query()->orderBy($column, 'asc');
    }

    public function hasOne($relatedModel, $foreignKey = null, $localKey = 'id'): HasOne
    {
        $relatedModel = $this->getModelInstance($relatedModel);
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        return new HasOne($relatedModel::query(), $this, $foreignKey, $localKey);
    }

    public function hasMany($relatedModel, $foreignKey = null, $localKey = 'id'): HasMany
    {
        $relatedModel = $this->getModelInstance($relatedModel);
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        return new HasMany($relatedModel::query(), $this, $foreignKey, $localKey);
    }

    public function belongsTo($relatedModel, $foreignKey = null, $ownerKey = 'id'): BelongsTo
    {
        $relatedModel = $this->getModelInstance($relatedModel);
        $foreignKey = $foreignKey ?: $this->getForeignKey($relatedModel);

        return new BelongsTo($relatedModel::query(), $this, $foreignKey, $ownerKey);
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    public function setAttribute($key, $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getAttribute($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * @throws Exception
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        $method = 'get' . ucfirst($key);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return null;
    }

    public function __set($key, $value)
    {
        $method = 'set' . ucfirst($key);
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else {
            $this->setAttribute($key, $value);
        }
    }

    public function setRelation($relation, $value): static
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    public function __call($method, $parameters)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$parameters);
        }

        // Getter
        if (str_starts_with($method, 'get')) {
            $property = lcfirst(substr($method, 3));
            return $this->getAttribute($property);
        }

        // Setter
        if (str_starts_with($method, 'set')) {
            $property = lcfirst(substr($method, 3));
            $this->setAttribute($property, $parameters[0]);
            return $this;
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function getRelation($relation)
    {
        return $this->relations[$relation] ?? null;
    }

    // CRUD işlemleri
    public function save(): bool
    {
        try {
            if (isset($this->attributes['id'])) {
                return $this->update();
            }
            return $this->insert();
        } catch (PDOException $e) {
            $this->getLogger()->error("Database error: " . $e->getMessage());
            return false;
        }
    }

    public function delete(): bool
    {
        return static::query()->where('id', $this->attributes['id'])->delete()->execute();
    }

    // Yardımcı metodlar
    protected function getModelInstance($model)
    {
        return is_string($model) ? new $model : $model;
    }

    protected function getForeignKey($model = null): string
    {
        $model = $model ?: $this;
        return strtolower(class_basename($model)) . '_id';
    }

    protected function newQuery(): QueryBuilder
    {
        return (new QueryBuilder($this->getConnection(), $this->getLogger()))->setModel($this);
    }

    /**
     * @throws Exception
     */
    protected function getRelationValue($key)
    {
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    public function relationLoaded($key): bool
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * @throws Exception
     */
    protected function getRelationshipFromMethod($method)
    {
        $relation = $this->$method();

        if (!$relation instanceof Relation) {
            throw new Exception('Relationship method must return an object of type Relation');
        }

        $results = $relation->getResults();
        $this->setRelation($method, $results);

        return $results;
    }

    protected function insert(): bool
    {
        $query = static::query();
        $id = $query->insert($this->attributes)->execute();

        if ($id) {
            $this->setAttribute('id', $id);
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    protected function update(): bool
    {
        if (empty($this->changes)) {
            return true;
        }

        $query = static::query()->where('id', $this->attributes['id']);
        $affected = $query->update($this->changes)->execute();

        if ($affected) {
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
        $this->changes = [];
    }

    // Koleksiyon işlemleri
    public function newCollection(array $models = []): Collection
    {
        return new Collection($models);
    }

    public static function hydrate(array $items)
    {
        $instance = new static;
        return array_map(function ($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $items);
    }

    public static function newFromBuilder($attributes = []): static
    {
        $model = new static;
        $model->setRawAttributes($attributes, true);
        return $model;
    }

    public function setRawAttributes(array $attributes, $sync = false): self
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    // Eager loading
    public static function eagerLoad($models, $relations)
    {
        foreach ($relations as $relation) {
            $models = (new static)->$relation()->eagerLoadRelation($models);
        }

        return $models;
    }

    public function load($relations): static
    {
        $query = $this->newQuery()->with($relations);
        $query->eagerLoadRelations([$this]);
        return $this;
    }
}