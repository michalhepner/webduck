<?php

declare(strict_types = 1);

namespace Webduck\Domain\Audit;

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

    public function exclude(string $auditName): self
    {
        return $this->excludeMultiple([$auditName]);
    }

    public function excludeMultiple(array $auditNames): self
    {
        $copy = new static();
        foreach ($this->items as $item) {
            if (!in_array($item->getName(), $auditNames, true)) {
                $copy->add($item);
            }
        }

        return $copy;
    }

    public function copy(): self
    {
        return new static($this->items);
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
