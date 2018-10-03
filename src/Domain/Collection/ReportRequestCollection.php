<?php

declare(strict_types = 1);

namespace Webduck\Domain\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use UnexpectedValueException;
use Webduck\Domain\Model\ReportRequest;

class ReportRequestCollection implements IteratorAggregate, Countable
{
    /**
     * @var ReportRequest[]
     */
    protected $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(ReportRequest $item): self
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

    public function first(): ?ReportRequest
    {
        foreach ($this->items as $item) {
            return $item;
        }

        return null;
    }

    public function last(): ?ReportRequest
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

    public function get(string $uuid): ReportRequest
    {
        foreach ($this->items as $item) {
            if ($item->getReportUuid() === $uuid) {
                return $item;
            }
        }

        throw new UnexpectedValueException();
    }

    public function has(string $uuid): bool
    {
        foreach ($this->items as $item) {
            if ($item->getReportUuid() === $uuid) {
                return true;
            }
        }

        return false;
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

    public function uasort(callable $cmp)
    {
        uasort($this->items, $cmp);
    }
}
