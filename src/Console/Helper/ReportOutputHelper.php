<?php

namespace Webduck\Console\Helper;

use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Domain\Model\Report;
use Webduck\Domain\Transformer\ReportToConsoleOutputTransformer;
use Webduck\Domain\Transformer\ReportToHtmlTransformer;
use Webduck\Domain\Transformer\ReportToJsonTransformer;

class ReportOutputHelper
{
    const FORMAT_HTML = 'html';
    const FORMAT_JSON = 'json';
    const FORMAT_TEXT = 'text';
    const ALLOWED_FORMATS = [
        self::FORMAT_HTML,
        self::FORMAT_JSON,
        self::FORMAT_TEXT,
    ];

    /**
     * @var ReportToHtmlTransformer
     */
    protected $reportToHtmlTransformer;

    /**
     * @var ReportToJsonTransformer
     */
    protected $reportToJsonTransformer;

    /**
     * @var ReportToConsoleOutputTransformer
     */
    protected $reportToConsoleOutputTransformer;

    public function __construct(ReportToHtmlTransformer $reportToHtmlTransformer, ReportToJsonTransformer $reportToJsonTransformer, ReportToConsoleOutputTransformer $reportToConsoleOutputTransformer)
    {
        $this->reportToHtmlTransformer = $reportToHtmlTransformer;
        $this->reportToJsonTransformer = $reportToJsonTransformer;
        $this->reportToConsoleOutputTransformer = $reportToConsoleOutputTransformer;
    }

    public function render(Report $report, string $format, OutputInterface $output)
    {
        switch ($format) {
            case self::FORMAT_HTML:
                $reportOutput = $this->reportToHtmlTransformer->transform($report);
                break;
            case self::FORMAT_JSON:
                $reportOutput = $this->reportToJsonTransformer->transform($report);
                break;
            case self::FORMAT_TEXT:
                $reportOutput = $this->reportToConsoleOutputTransformer->transform($report, true);
                break;
            default:
                throw new \InvalidArgumentException(sprintf(
                    'Unknown format \'%s\' allowed values are %s',
                    $format,
                    implode(', ', self::ALLOWED_FORMATS)
                ));

        }

        $output->writeln($reportOutput);
    }
}
