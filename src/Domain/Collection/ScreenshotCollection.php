<?php

declare(strict_types = 1);

namespace Webduck\Domain\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Webduck\Domain\Model\Screenshot;

class ScreenshotCollection implements IteratorAggregate, Countable
{
    /**
     * @var Screenshot[]
     */
    protected $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(Screenshot $item): self
    {
        $this->items[] = $item;

        return $this;
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

    public function walk(callable $func): void
    {
        array_walk($this->items, $func);
    }

    public function map(callable $func)
    {
        return array_map($func, $this->items);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function count()
    {
        return count($this->items);
    }
}
