<?php

declare(strict_types = 1);

namespace Webduck\Domain\Model;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Webduck\Domain\Collection\ReportPageCollection;

class Report
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var ReportPageCollection
     */
    protected $pages;

    /**
     * @param string                            $uuid
     * @param string                            $name
     * @param ReportPage[]|ReportPageCollection $pages
     */
    public function __construct(string $uuid, string $name, $pages = [])
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->setPages($pages);
    }

    /**
     * @param string                            $name
     * @param ReportPage[]|ReportPageCollection $pages
     *
     * @return self
     */
    public static function create(string $name, $pages = []): self
    {
        return new static(Uuid::uuid4()->toString(), $name, $pages);
    }

    public function getUuid(): string
    {
        return $this->uuid;
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
