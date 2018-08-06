<?php

declare(strict_types = 1);

namespace Webduck\Audit;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class AuditCollection implements IteratorAggregate, Countable
{
    /**
     * @var AuditInterface[]
     */
    protected $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(AuditInterface $item): self
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
}
