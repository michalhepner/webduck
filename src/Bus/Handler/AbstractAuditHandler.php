<?php

declare(strict_types = 1);

namespace Webduck\Bus\Handler;

use Webduck\Bus\Event\ReportPageEmittedEvent;
use Webduck\Dispatcher\DispatcherAwareInterface;
use Webduck\Dispatcher\DispatcherAwareTrait;
use Webduck\Domain\Audit\AuditCollection;
use Webduck\Domain\Audit\AuditInterface;
use Webduck\Domain\Collection\BrowseCollection;
use Webduck\Domain\Collection\InsightCollection;
use Webduck\Domain\Collection\UriCollection;
use Webduck\Domain\Model\Browse;
use Webduck\Domain\Model\Report;
use Webduck\Domain\Model\ReportPage;
use Webduck\Domain\Provider\BrowseCollectionProvider;

abstract class AbstractAuditHandler implements DispatcherAwareInterface
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

    protected function prepareReport(
        string $uuid,
        string $name,
        UriCollection $uris,
        AuditCollection $audits,
        ?bool $screenshot = null,
        ?string $username = null,
        ?string $password = null
    ): Report {
        $report = new Report($uuid, $name);

        $emitCallback = function (BrowseCollection $browses) use ($report, $uris, $audits) {
            /** @var Browse $browse */
            foreach ($browses as $browse) {
                $insightsCollections = [];
                /** @var AuditInterface $audit */
                foreach ($audits as $audit) {
                    $insightsCollections[] = $audit->execute($browse);
                }

                $reportPage = new ReportPage($browse->getUri(), InsightCollection::merge(...$insightsCollections), $browse->getScreenshot());
                $this->dispatch(ReportPageEmittedEvent::NAME, new ReportPageEmittedEvent($reportPage, $uris));
                $report->addPage($reportPage);
            }
        };

        $screenshot = $screenshot !== null ? $screenshot : false;

        $emitOptions = ['screenshot' => $screenshot];
        !empty($username) && $emitOptions['username'] = $username;
        !empty($password) && $emitOptions['password'] = $password;

        $this->browseCollectionProvider->emit($uris, $emitCallback, $emitOptions);
        $report->getPages()->uasort(function (ReportPage $reportPage1, ReportPage $reportPage2) {
            return strcmp($reportPage1->getUri()->__toString(), $reportPage2->getUri()->__toString());
        });

        return $report;
    }
}
