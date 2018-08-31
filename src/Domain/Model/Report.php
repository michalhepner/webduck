<?php

declare(strict_types = 1);

namespace Webduck\Domain\Model;

use InvalidArgumentException;
use Webduck\Domain\Collection\ReportPageCollection;

class Report
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var ReportPageCollection
     */
    protected $pages;

    /**
     * @param string                            $name
     * @param ReportPage[]|ReportPageCollection $pages
     */
    public function __construct(string $name, $pages = [])
    {
        $this->name = $name;
        $this->setPages($pages);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPages(): ReportPageCollection
    {
        return $this->pages;
    }

    /**
     * @param ReportPage[]|ReportPageCollection $pages
     *
     * @return Report
     */
    public function setPages($pages): self
    {
        if (is_array($pages)) {
            $pages = new ReportPageCollection($pages);
        } elseif (!$pages instanceof ReportPageCollection) {
            throw new InvalidArgumentException(sprintf(
                'Argument 0 passed to %s must be an array or instance of %s',
                __METHOD__,
                ReportPageCollection::class
            ));
        }

        $this->pages = $pages;

        return $this;
    }

    public function addPage(ReportPage $page): self
    {
        $this->pages->add($page);

        return $this;
    }
}
