<?php

declare(strict_types = 1);

namespace Webduck\Domain\Transformer;

use Webduck\Domain\Model\Insight;
use Webduck\Domain\Model\ReportPage;

class ReportPageToArrayTransformer
{
    /**
     * @var InsightToArrayTransformer
     */
    protected $insightToArrayTransformer;

    public function __construct(InsightToArrayTransformer $insightToArrayTransformer)
    {
        $this->insightToArrayTransformer = $insightToArrayTransformer;
    }

    public function transform(ReportPage $reportPage): array
    {
        return [
            'uri' => $reportPage->getUri()->__toString(),
            'screenshot' => !$reportPage->getScreenshot() ? null : [
                'media_type' => $reportPage->getScreenshot()->getMediaType(),
                'is_base_64' => $reportPage->getScreenshot()->getIsBase64(),
                'data' => $reportPage->getScreenshot()->getData(),
            ],
            'insights' => $reportPage->getInsights()->map(function (Insight $insight) {
                return $this->insightToArrayTransformer->transform($insight);
            })
        ];
    }
}
