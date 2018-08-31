<?php

declare(strict_types = 1);

namespace Webduck\Bus\Event;

use Symfony\Component\EventDispatcher\Event;
use Webduck\Domain\Model\ReportPage;

class ReportPageEmittedEvent extends Event
{
    const NAME = 'report_page_emitted';

    /**
     * @var ReportPage
     */
    protected $reportPage;

    public function __construct(ReportPage $reportPage)
    {
        $this->reportPage = $reportPage;
    }

    public function getReportPage(): ReportPage
    {
        return $this->reportPage;
    }
}
