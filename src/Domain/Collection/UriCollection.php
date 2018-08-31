<?php

declare(strict_types = 1);

namespace Webduck\Domain\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Webduck\Domain\Model\Uri;

class UriCollection implements IteratorAggregate, Countable
{
    /**
     * @var Uri[]
     */
    protected $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * @param string|Uri $item
     *
     * @return self
     */
    public function add($item): self
    {
        if (is_string($item)) {
            $item = Uri::createFromString($item);
        } elseif (!$item instanceof Uri) {
            throw new \InvalidArgumentException(sprintf(
                'Argument 0 passed to %s must be a string or instance of %s',
                __METHOD__,
                Uri::class
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

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function count()
    {
        return count($this->items);
    }

    public function unique(): self
    {
        $uniqueStrings = [];
        $uniqueItems = [];

        foreach ($this->items as $item) {
            if (array_search($item->__toString(), $uniqueStrings, true) === false) {
                $uniqueStrings[] = $item->__toString();
                $uniqueItems[] = $item;
            }
        }

        return new static($uniqueItems);
    }

    public function uasort(callable $cmp)
    {
        uasort($this->items, $cmp);
    }
}
