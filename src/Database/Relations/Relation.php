<?php

namespace Yabasi\Database\Relations;

use Yabasi\Database\Model;
use Yabasi\Database\QueryBuilder;
use Yabasi\Support\Collection;

abstract class Relation
{
    protected $query;
    protected $parent;
    protected $related;
    protected $foreignKey;
    protected $localKey;

    public function __construct(QueryBuilder $query, Model $parent, $foreignKey, $localKey)
    {
        $this->query = $query;
        $this->parent = $parent;
        $this->related = $query->getModel();
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function getResults()
    {
        return $this->query->get();
    }

    public function addEagerConstraints(array $models)
    {
        $this->query->whereIn(
            $this->foreignKey, $this->getKeys($models, $this->localKey)
        );
    }

    protected function getKeys(array $models, $key)
    {
        return array_unique(array_values(
            array_map(function ($model) use ($key) {
                return $model->getAttribute($key);
            }, $models)
        ));
    }

    abstract public function initRelation(array $models, $relation);
    abstract public function match(array $models, Collection $results, $relation);
}