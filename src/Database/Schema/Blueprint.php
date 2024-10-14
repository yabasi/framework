<?php

namespace Yabasi\Database\Schema;

class Blueprint
{
    protected string $table;
    protected array $columns = [];
    protected array $indexes = [];
    protected array $foreignKeys = [];
    protected ?string $primaryKey = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id()
    {
        $this->addColumn('id', 'bigint', ['unsigned' => true, 'autoIncrement' => true]);
        return $this;
    }

    public function string(string $column, int $length = 255)
    {
        $this->addColumn($column, 'varchar', ['length' => $length]);
        return $this;
    }

    public function integer(string $column)
    {
        $this->addColumn($column, 'int');
        return $this;
    }

    public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): self
    {
        $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
        return $this;
    }

    public function text(string $column)
    {
        $this->addColumn($column, 'text');
        return $this;
    }

    public function boolean(string $column)
    {
        $this->addColumn($column, 'tinyint', ['length' => 1]);
        return $this;
    }

    public function date(string $column): self
    {
        $this->addColumn('date', $column);
        return $this;
    }

    public function dateTime(string $column): self
    {
        $this->addColumn('dateTime', $column);
        return $this;
    }

    public function timestamp(string $column): self
    {
        return $this->addColumn('timestamp', $column, ['nullable' => true]);
    }

    public function timestamps()
    {
        $this->addColumn('created_at', 'timestamp');
        $this->addColumn('updated_at', 'timestamp');
        return $this;
    }

    public function decimal(string $column, int $precision = 8, int $scale = 2)
    {
        $this->addColumn($column, 'decimal', ['precision' => $precision, 'scale' => $scale]);
        return $this;
    }

    public function float(string $column): self
    {
        $this->addColumn('float', $column);
        return $this;
    }

    public function enum(string $column, array $allowed): self
    {
        $this->addColumn('enum', $column, compact('allowed'));
        return $this;
    }

    public function json(string $column): self
    {
        $this->addColumn('json', $column);
        return $this;
    }

    public function nullable(): self
    {
        $lastColumn = &$this->columns[count($this->columns) - 1];
        $lastColumn['nullable'] = true;
        return $this;
    }

    public function default($value): self
    {
        $this->columns[count($this->columns) - 1]['default'] = $value;
        return $this;
    }

    public function unique(string $column): self
    {
        $this->addIndex('unique', $column);
        return $this;
    }

    public function index(string $column): self
    {
        $this->addIndex('index', $column);
        return $this;
    }

    public function foreignId(string $column): self
    {
        $this->unsignedBigInteger($column);
        return $this;
    }

    public function unsignedBigInteger(string $column): self
    {
        return $this->bigInteger($column, false, true);
    }

    public function references(string $column): ForeignKeyDefinition
    {
        $foreignKey = new ForeignKeyDefinition($this, $column);
        $this->foreignKeys[] = $foreignKey;
        return $foreignKey;
    }

    protected function addColumn(string $name, string $type, array $options = [])
    {
        $this->columns[] = array_merge(['name' => $name, 'type' => $type], $options);
    }

    protected function addIndex(string $type, string $column): void
    {
        $this->indexes[] = compact('type', 'column');
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }
}