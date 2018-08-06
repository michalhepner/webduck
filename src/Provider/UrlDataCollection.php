<?php

declare(strict_types = 1);

namespace Webduck\Provider;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use UnexpectedValueException;

class UrlDataCollection implements IteratorAggregate, Countable
{
    /**
     * @var UrlData[]
     */
    protected $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public static function createFromArray(array $data): self
    {
        $obj = new self();
        foreach ($data as $url => $urlData) {
            $obj->add(UrlData::createFromArray($url, $urlData));
        }

        return $obj;
    }

    public function add(UrlData $item): self
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

    public function first(): ?UrlData
    {
        foreach ($this->items as $item) {
            return $item;
        }

        return null;
    }

    public function last(): ?UrlData
    {
        $tmpItem = null;
        foreach ($this->items as $item) {
            $tmpItem = $item;
        }

        return $tmpItem;
    }

    public function get(string $url): UrlData
    {
        foreach ($this->items as $item) {
            if ($item->getUrl() === $url) {
                return $item;
            }
        }

        throw new UnexpectedValueException();
    }

    public function has(string $url): bool
    {
        foreach ($this->items as $item) {
            if ($item->getUrl() === $url) {
                return true;
            }
        }

        return false;
    }
}
