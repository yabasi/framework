<?php

namespace Yabasi\Database\Relations;

use Yabasi\Support\Collection;

class BelongsTo extends Relation
{
    public function getResults()
    {
        return $this->query->where($this->localKey, $this->parent->{$this->foreignKey})->first();
    }

    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, null);
        }

        return $models;
    }

    public function match(array $models, Collection $results, $relation)
    {
        $foreign = $this->foreignKey;
        $owner = $this->localKey;

        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->getAttribute($owner)] = $result;
        }

        foreach ($models as $model) {
            if (isset($dictionary[$model->$foreign])) {
                $model->setRelation($relation, $dictionary[$model->$foreign]);
            }
        }

        return $models;
    }
}