<?php

declare(strict_types = 1);

namespace Webduck\Domain\Transformer;

use Webduck\Domain\Model\Insight;
use Webduck\Domain\Model\ReportPage;

class ReportPageToConsoleOutputTransformer
{
    public function transform(
        ReportPage $reportPage,
        bool $includeData = false,
        bool $includeDateTime = false,
        ?int $uriIndex = null,
        ?int $uriCount = null
    ): string {
        $headerLine = $includeDateTime ? date(DATE_ATOM).' ' : '';
        $headerLine .= ($uriCount !== null ? '['.$uriIndex.'/'.$uriCount.'] ' : '').$reportPage->getUri()->__toString();

        $lines = [
            sprintf('<comment>%s</comment>', str_repeat('-', strlen($headerLine))),
            sprintf('<comment>%s</comment>', $headerLine),
            sprintf('<comment>%s</comment>', str_repeat('-', strlen($headerLine))),
        ];

        foreach ($reportPage->getInsights() as $insight) {
            switch ($insight->getMark()) {
                case Insight::MARK_ERROR:
                    $color = 'red';
                    break;
                case Insight::MARK_WARNING:
                    $color = 'yellow';
                    break;
                default:
                    $color = 'default';
            }

            $line = sprintf('- <fg=%s;options=bold>%s:</> %s', $color, $insight->getName(), $insight->getMessage());
            $line .= $includeData ? sprintf(' <fg=blue>%s</>', json_encode($insight->getData())) : '';
            $lines[] = $line;
        }

        return implode(PHP_EOL, $lines);
    }
}
