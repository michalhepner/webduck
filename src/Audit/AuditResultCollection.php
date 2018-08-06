<?php

declare(strict_types = 1);

namespace Webduck\Audit;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class AuditResultCollection implements IteratorAggregate, Countable
{
    /**
     * @var AuditResult[]
     */
    protected $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(AuditResult $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function remove(AuditResult $item): self
    {
        foreach ($this->items as $key => $tmpItem) {
            if ($tmpItem === $item) {
                unset($this->items[$key]);
            }
        }

        return $this;
    }

    public function has(AuditResult $item): bool
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

    public static function merge(self ...$auditResultCollections): self
    {
        $result = new self();
        foreach ($auditResultCollections as $auditResultCollection) {
            foreach ($auditResultCollection as $auditResult) {
                $result->add($auditResult);
            }
        }

        return $result;
    }
}
