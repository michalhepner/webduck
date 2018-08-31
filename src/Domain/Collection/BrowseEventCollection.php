<?php

declare(strict_types = 1);

namespace Webduck\Domain\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Webduck\Domain\Model\BrowseEvent;

class BrowseEventCollection implements IteratorAggregate, Countable
{
    protected $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(BrowseEvent $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function remove(BrowseEvent $item): self
    {
        foreach ($this->items as $key => $tmpItem) {
            if ($tmpItem === $item) {
                unset($this->items[$key]);
            }
        }

        return $this;
    }

    public function has(BrowseEvent $item): bool
    {
        foreach ($this->items as $key => $tmpItem) {
            if ($tmpItem === $item) {
                return true;
            }
        }

        return false;
    }

    public function meld(...$collections): self
    {
        return static::merge($this, ...$collections);
    }

    public static function merge(...$collections): self
    {
        $obj = new static();
        foreach ($collections as $collection) {
            foreach ($collection as $item) {
                $obj->add($item);
            }
        }

        return $obj;
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
