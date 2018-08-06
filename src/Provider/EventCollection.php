<?php

declare(strict_types = 1);

namespace Webduck\Provider;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class EventCollection implements IteratorAggregate, Countable
{
    protected $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(Event $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function remove(Event $item): self
    {
        foreach ($this->items as $key => $tmpItem) {
            if ($tmpItem === $item) {
                unset($this->items[$key]);
            }
        }

        return $this;
    }

    public function has(Event $item): bool
    {
        foreach ($this->items as $key => $tmpItem) {
            if ($tmpItem === $item) {
                return true;
            }
        }

        return false;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function count()
    {
        return count($this->items);
    }

    public function map(callable $callback)
    {
        return array_map($callback, $this->items);
    }

    public function filter(callable $callback): self
    {
        return new self(array_filter($this->items, $callback));
    }
}
