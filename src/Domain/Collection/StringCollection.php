<?php

declare(strict_types = 1);

namespace Webduck\Domain\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class StringCollection implements IteratorAggregate, Countable
{
    /**
     * @var string[]
     */
    protected $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(string $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function count()
    {
        return count($this->items);
    }

    public function first(): ?string
    {
        foreach ($this->items as $item) {
            return $item;
        }

        return null;
    }

    public function last(): ?string
    {
        $tmpItem = null;
        foreach ($this->items as $item) {
            $tmpItem = $item;
        }

        return $tmpItem;
    }

    public function copy(): self
    {
        return new self($this->items);
    }

    public function has(string $item): bool
    {
        foreach ($this->items as $tmpItem) {
            if ($tmpItem === $item) {
                return true;
            }
        }

        return false;
    }

    public function unique(): self
    {
        return new static(array_unique($this->items));
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

    public function toArray(): array
    {
        return $this->items;
    }

    public static function fromArray(array $arr): self
    {
        return new static($arr);
    }
}
