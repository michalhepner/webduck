<?php

declare(strict_types = 1);

namespace Webduck\Domain\Collection;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Webduck\Domain\Model\Screenshot;
use Webduck\Domain\Model\Uri;

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

    /**
     * @param Screenshot $item
     *
     * @return self
     */
    public function add(Screenshot $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * @param string|Uri $uri
     *
     * @return bool
     */
    public function hasForUri($uri): bool
    {
        if ($uri instanceof Uri) {
            $uri = $uri->__toString();
        } elseif(!is_string($uri)) {
            throw new InvalidArgumentException(sprintf(
                'Argument 0 provided to %s must be a string or an instance of %s',
                __METHOD__,
                Uri::class
            ));
        }

        foreach ($this->items as $item) {
            if ($item->getUri()->__toString() == $uri) {
                return true;
            }
        }

        return false;
    }

    public function getForUri($uri): ?Screenshot
    {
        if ($uri instanceof Uri) {
            $uri = $uri->__toString();
        } elseif(!is_string($uri)) {
            throw new InvalidArgumentException(sprintf(
                'Argument 0 provided to %s must be a string or an instance of %s',
                __METHOD__,
                Uri::class
            ));
        }

        foreach ($this->items as $item) {
            if ($item->getUri()->__toString() == $uri) {
                return $item;
            }
        }

        return null;
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
