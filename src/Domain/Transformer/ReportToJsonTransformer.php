<?php

declare(strict_types = 1);

namespace Webduck\Domain\Transformer;

use Webduck\Domain\Model\Report;
use Webduck\Domain\Model\ReportPage;

class ReportToJsonTransformer
{
    /**
     * @var ReportPageToArrayTransformer
     */
    protected $reportPageToArrayTransformer;

    public function __construct(ReportPageToArrayTransformer $reportPageToArrayTransformer)
    {
        $this->reportPageToArrayTransformer = $reportPageToArrayTransformer;
    }

    public function transform(Report $report, int $options = 0): string
    {
        return json_encode([
            'uuid' => $report->getUuid(),
            'name' => $report->getName(),
            'pages' => $report->getPages()->map(function (ReportPage $page) {
                return $this->reportPageToArrayTransformer->transform($page);
            }),
        ], $options);
    }
}
