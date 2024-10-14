<?php

namespace Yabasi\Database\Schema;

class ForeignKeyDefinition
{
    protected Blueprint $blueprint;
    protected string $column;
    protected string $onTable;
    protected string $onColumn;
    protected string $onDelete = 'RESTRICT';
    protected string $onUpdate = 'RESTRICT';

    public function __construct(Blueprint $blueprint, string $column)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
    }

    public function on(string $table): self
    {
        $this->onTable = $table;
        return $this;
    }

    public function references(string $column): self
    {
        $this->onColumn = $column;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = $action;
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = $action;
        return $this;
    }

    public function getDefinition(): array
    {
        return [
            'column' => $this->column,
            'onTable' => $this->onTable,
            'onColumn' => $this->onColumn,
            'onDelete' => $this->onDelete,
            'onUpdate' => $this->onUpdate,
        ];
    }
}