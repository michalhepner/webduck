<?php

declare(strict_types = 1);

namespace Webduck\Domain\Collection;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Webduck\Domain\Model\UriFilter;

class UriFilterCollection implements IteratorAggregate, Countable
{
    /**
     * @var UriFilter[]
     */
    protected $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * @param string|UriFilter $item
     *
     * @return self
     */
    public function add($item): self
    {
        if (is_string($item)) {
            $item = new UriFilter($item);
        } elseif(!$item instanceof UriFilter) {
            throw new InvalidArgumentException(sprintf(
                'Argument 0 passed to %s must be either a string or instance of %s',
                __METHOD__,
                UriFilter::class
            ));
        }

        $this->items[] = $item;

        return $this;
    }

    public function walk(callable $func): void
    {
        array_walk($this->items, $func);
    }

    public function map(callable $func)
    {
        return array_map($func, $this->items);
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

    public function getArrayCopy(): array
    {
        return $this->items;
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
