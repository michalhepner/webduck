<?php

namespace Webduck\Bus\Handler;

use Webduck\Bus\Command\AuditPageCommand;
use Webduck\Bus\Event\ReportPageEmittedEvent;
use Webduck\Bus\Event\UriQueuedEvent;
use Webduck\Dispatcher\DispatcherAwareInterface;
use Webduck\Dispatcher\DispatcherAwareTrait;
use Webduck\Domain\Audit\AuditInterface;
use Webduck\Domain\Collection\BrowseCollection;
use Webduck\Domain\Collection\InsightCollection;
use Webduck\Domain\Model\Browse;
use Webduck\Domain\Model\Report;
use Webduck\Domain\Model\ReportPage;
use Webduck\Domain\Model\Uri;
use Webduck\Domain\Provider\BrowseCollectionProvider;

class AuditPageHandler implements DispatcherAwareInterface
{
    use DispatcherAwareTrait;

    /**
     * @var BrowseCollectionProvider
     */
    protected $browseCollectionProvider;

    public function __construct(BrowseCollectionProvider $browseCollectionProvider)
    {
        $this->browseCollectionProvider = $browseCollectionProvider;
    }

    public function handle(AuditPageCommand $command): Report
    {
        $browseUris = $command->getUris()->unique();

        foreach ($browseUris as $uri) {
            $this->dispatch(UriQueuedEvent::NAME, new UriQueuedEvent($uri));
        }

        $uriHosts = array_unique($command->getUris()->map(function (Uri $uri) {
            return $uri->getHost();
        }));

        $report = new Report(sprintf('Site report for: %s', implode(', ', $uriHosts)));

        $emitCallback = function (BrowseCollection $browses) use ($command, $report) {
            /** @var Browse $browse */
            foreach ($browses as $browse) {
                $insightsCollections = [];
                /** @var AuditInterface $audit */
                foreach ($command->getAudits() as $audit) {
                    $insightsCollections[] = $audit->execute($browse);
                }

                $reportPage = new ReportPage($browse->getUri(), InsightCollection::merge(...$insightsCollections), $browse->getScreenshot());
                $this->dispatch(ReportPageEmittedEvent::NAME, new ReportPageEmittedEvent($reportPage));
                $report->addPage($reportPage);
            }
        };

        $emitOptions = ['screenshot' => $command->getShouldGenerateScreenshot()];
        $command->getUsername() && $emitOptions['username'] = $command->getUsername();
        $command->getPassword() && $emitOptions['password'] = $command->getPassword();

        $this->browseCollectionProvider->emit($browseUris, $emitCallback, $emitOptions);
        $report->getPages()->uasort(function (ReportPage $reportPage1, ReportPage $reportPage2) {
            return strcmp($reportPage1->getUri()->__toString(), $reportPage2->getUri()->__toString());
        });

        return $report;
    }
}
