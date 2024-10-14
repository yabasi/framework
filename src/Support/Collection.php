<?php

namespace Yabasi\Support;

use ArrayAccess;
use Countable;
use IteratorAggregate;

class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var T[]
     */
    protected $items = [];

    public function __construct($items = [])
    {
        $this->items = $items;
    }

    /**
     * @return T[]
     */
    public function all()
    {
        return $this->items;
    }

    public function pluck($value, $key = null): static
    {
        $results = [];

        foreach ($this->items as $item) {
            $itemValue = is_array($item) ? ($item[$value] ?? null) : ($item->$value ?? null);

            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
                if (!is_null($itemKey)) {
                    $results[$itemKey] = $itemValue;
                }
            }
        }

        return new static($results);
    }

    public function flatten(): static
    {
        $result = [];

        foreach ($this->items as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $result = array_merge($result, array_values($item));
            }
        }

        return new static($result);
    }

    public function implode($value, $glue = null): string
    {
        $first = $this->first();

        if (is_array($first) || is_object($first)) {
            return implode($glue, $this->pluck($value)->all());
        }

        return implode($value, $this->items);
    }

    public function first()
    {
        return reset($this->items);
    }

    public function last()
    {
        return end($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return \ArrayIterator|T[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $items));
    }

    public function filter(callable $callback = null): self
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }
        return new static(array_filter($this->items));
    }
}