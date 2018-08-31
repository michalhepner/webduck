<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Bus\Event\ReportPageEmittedEvent;
use Webduck\Bus\Event\UriQueuedEvent;
use Webduck\Dispatcher\DispatcherAwareInterface;
use Webduck\Dispatcher\DispatcherAwareTrait;
use Webduck\Domain\Audit\ConsoleErrorsAudit;
use Webduck\Domain\Audit\AuditCollection;
use Webduck\Domain\Audit\ExceptionAudit;
use Webduck\Domain\Audit\PageSizeAudit;
use Webduck\Domain\Audit\ResourceLoadAudit;
use Webduck\Domain\Audit\ResourceSizeAudit;
use Webduck\Domain\Audit\SecurityAudit;
use Webduck\Domain\Audit\ViolationAudit;
use Webduck\Domain\Model\Report;
use Webduck\Domain\Transformer\ReportPageToConsoleOutputTransformer;
use Webduck\Domain\Transformer\ReportToConsoleOutputTransformer;
use Webduck\Domain\Transformer\ReportToHtmlTransformer;
use Webduck\Domain\Transformer\ReportToJsonTransformer;

abstract class AbstractAuditCommand extends ContainerAwareCommand implements DispatcherAwareInterface
{
    use DispatcherAwareTrait;

    const OUTPUT_TEXT = 'text';
    const OUTPUT_JSON = 'json';
    const OUTPUT_HTML = 'html';

    const ALLOWED_OUTPUTS = [
        self::OUTPUT_TEXT,
        self::OUTPUT_JSON,
        self::OUTPUT_HTML,
    ];

    protected function configure()
    {
        $this
            ->addOption('audit-violations', null, InputOption::VALUE_NONE)
            ->addOption('audit-resource-load', null, InputOption::VALUE_NONE)
            ->addOption('username', 'u', InputOption::VALUE_REQUIRED)
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED)
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Defines what type of output is expected. Possible values are \'text\' or \'html\'.', self::OUTPUT_TEXT)
            ->addOption('save-html', null, InputOption::VALUE_REQUIRED, 'Defines where to save the report in HTML format.')
            ->addOption('save-json', null, InputOption::VALUE_REQUIRED, 'Defines where to save the report in JSON format.')
            ->addOption('save-text', null, InputOption::VALUE_REQUIRED, 'Defines where to save the report in text format.')
            ->addOption('screenshot', null, InputOption::VALUE_NONE, 'Should screenshots be generated in the report (works only for HTML and JSON output)?')
        ;
    }

    protected function getAudits(InputInterface $input): AuditCollection
    {
        return new AuditCollection(array_filter([
            new ConsoleErrorsAudit(),
            new ExceptionAudit(),
            new PageSizeAudit(5 * 1024 * 1024),
            $input->getOption('audit-resource-load') ? new ResourceLoadAudit(1000) : null,
            new ResourceSizeAudit(1024 * 1024),
            new SecurityAudit(),
            $input->getOption('audit-violations') ? new ViolationAudit() : null,
        ]));
    }

    protected function attachListeners(OutputInterface $output)
    {
        if ($this->dispatcher) {
            $uriCount = 0;
            $currentUri = 0;

            $this->dispatcher->addListener(UriQueuedEvent::NAME, function (UriQueuedEvent $event) use ($output, &$uriCount) {
                $uriCount++;
                fwrite(STDERR, $output->getFormatter()->format(sprintf(
                    "<comment>Queued URI %s</comment>\n",
                    $event->getUri()->__toString()
                )));
            });
            $this->dispatcher && $this->dispatcher->addListener(
                ReportPageEmittedEvent::NAME,
                function (ReportPageEmittedEvent $event) use ($output, &$uriCount, &$currentUri) {
                    $currentUri++;
                    fwrite(STDERR, $output->getFormatter()->format(sprintf(
                        "%s\n",
                        $this->getContainer()->get(ReportPageToConsoleOutputTransformer::class)->transform(
                            $event->getReportPage(),
                            $output->isVerbose(),
                            true,
                            $currentUri,
                            $uriCount
                        )
                    )));
                }
            );
        }
    }

    protected function outputReport(Report $report, InputInterface $input, OutputInterface $output): void
    {
        switch ($input->getOption('output')) {
            case self::OUTPUT_HTML:
                $reportOutput = $this->getContainer()->get(ReportToHtmlTransformer::class)->transform($report);
                break;
            case self::OUTPUT_JSON:
                $reportOutput = $this->getContainer()->get(ReportToJsonTransformer::class)->transform($report);
                break;
            default:
                $reportOutput = $this->getContainer()->get(ReportToConsoleOutputTransformer::class)->transform($report, true);

        }

        $output->writeln($reportOutput);
    }
}
