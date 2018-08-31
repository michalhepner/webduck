<?php

declare(strict_types = 1);

namespace Webduck\Domain\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Webduck\Domain\Model\Insight;

class InsightCollection implements IteratorAggregate, Countable
{
    /**
     * @var Insight[]
     */
    protected $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(Insight $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function remove(Insight $item): self
    {
        foreach ($this->items as $key => $tmpItem) {
            if ($tmpItem === $item) {
                unset($this->items[$key]);
            }
        }

        return $this;
    }

    public function has(Insight $item): bool
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

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function map(callable $callback)
    {
        return array_map($callback, $this->items);
    }

    public function filter(callable $callback): self
    {
        return new self(array_filter($this->items, $callback));
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

    public function some(callable $func): bool
    {
        foreach ($this->items as $item) {
            if ($func($item)) {
                return true;
            }
        }

        return false;
    }

    public function hasErrors(): bool
    {
        return $this->some(function (Insight $insight) {
            return $insight->isMarkError();
        });
    }

    public function hasWarnings(): bool
    {
        return $this->some(function (Insight $insight) {
            return $insight->isMarkWarning();
        });
    }

    public function hasOks(): bool
    {
        return $this->some(function (Insight $insight) {
            return $insight->isMarkOk();
        });
    }
}
