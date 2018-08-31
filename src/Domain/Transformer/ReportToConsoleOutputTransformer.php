<?php

declare(strict_types = 1);

namespace Webduck\Domain\Transformer;

use Webduck\Domain\Model\Report;
use Webduck\Domain\Model\ReportPage;

class ReportToConsoleOutputTransformer
{
    /**
     * @var ReportPageToConsoleOutputTransformer
     */
    protected $reportPageToConsoleOutputTransformer;

    public function __construct(ReportPageToConsoleOutputTransformer $reportPageToConsoleOutputTransformer)
    {
        $this->reportPageToConsoleOutputTransformer = $reportPageToConsoleOutputTransformer;
    }

    public function transform(Report $report, bool $includeData = false): string
    {
        return implode(PHP_EOL, $report->getPages()->map(function (ReportPage $reportPage) use ($includeData) {
            return $this->reportPageToConsoleOutputTransformer->transform($reportPage, $includeData);
        }));
    }
}
