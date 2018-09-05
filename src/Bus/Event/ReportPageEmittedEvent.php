<?php

declare(strict_types = 1);

namespace Webduck\Bus\Event;

use Symfony\Component\EventDispatcher\Event;
use Webduck\Domain\Collection\UriCollection;
use Webduck\Domain\Model\ReportPage;

class ReportPageEmittedEvent extends Event
{
    const NAME = 'report_page_emitted';

    /**
     * @var ReportPage
     */
    protected $reportPage;

    /**
     * @var UriCollection
     */
    protected $reportUris;

    public function __construct(ReportPage $reportPage, UriCollection $reportUris)
    {
        $this->reportPage = $reportPage;
        $this->reportUris = $reportUris;
    }

    public function getReportPage(): ReportPage
    {
        return $this->reportPage;
    }

    public function getReportUris(): UriCollection
    {
        return $this->reportUris;
    }
}
