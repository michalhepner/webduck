<?php

namespace Webduck\Bus\Handler;

use GuzzleHttp\Client;
use Siteqa\Test\Domain\Model\Sitemap;
use Siteqa\Test\Provider\HttpSuiteProvider;
use Siteqa\Test\Domain\Model\Uri as SiteqaUri;
use Siteqa\Test\Provider\SitemapResultProvider;
use Webduck\Bus\Command\AuditSitemapCommand;
use Webduck\Bus\Event\ReportPageEmittedEvent;
use Webduck\Bus\Event\UriQueuedEvent;
use Webduck\Dispatcher\DispatcherAwareInterface;
use Webduck\Dispatcher\DispatcherAwareTrait;
use Webduck\Domain\Audit\AuditInterface;
use Webduck\Domain\Collection\BrowseCollection;
use Webduck\Domain\Collection\InsightCollection;
use Webduck\Domain\Collection\UriCollection;
use Webduck\Domain\Model\Browse;
use Webduck\Domain\Model\Report;
use Webduck\Domain\Model\ReportPage;
use Webduck\Domain\Model\Uri;
use Webduck\Domain\Provider\BrowseCollectionProvider;

class AuditSitemapHandler implements DispatcherAwareInterface
{
    use DispatcherAwareTrait;

    /**
     * @var BrowseCollectionProvider
     */
    protected $browseCollectionProvider;

    /**
     * @var string[]
     */
    protected $defaultUriFilters;

    public function __construct(BrowseCollectionProvider $browseCollectionProvider)
    {
        $this->browseCollectionProvider = $browseCollectionProvider;
        $this->defaultUriFilters = [];
    }

    public function handle(AuditSitemapCommand $command): Report
    {
        $sitemapUrl = SiteqaUri::createFromString($command->getUri()->__toString());
        if ($command->hasUserInfo()) {
            $sitemapUrl = $sitemapUrl->withUserInfo($command->getUsername(), $command->getPassword());
        }

        $sitemapProvider = new SitemapResultProvider(new HttpSuiteProvider(new Client()));
        $sitemap = new Sitemap($sitemapUrl);
        $sitemapResult = $sitemapProvider->provide($sitemap);

        $urls = [];
        /** @var SiteqaUri $uri */
        foreach ($sitemapResult->getUris() as $uri) {
            $uri = !$uri->hasHost() ? $sitemapUrl->getHost() : $uri;
            $uri = empty($uri->getScheme()) ? $uri->withScheme($sitemapUrl->getScheme()) : $uri;
            $urls[] = $uri->__toString();
        }

        $browseUris = new UriCollection(array_unique($urls));
        $browseUris = $browseUris->unique();

        foreach ($browseUris as $uri) {
            $this->dispatch(UriQueuedEvent::NAME, new UriQueuedEvent($uri));
        }

        $report = new Report(sprintf('Site report for: %s', $sitemapUrl->getHost()));

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

        $this->browseCollectionProvider->emit($browseUris, $emitCallback);
        $report->getPages()->uasort(function (ReportPage $reportPage1, ReportPage $reportPage2) {
            return strcmp($reportPage1->getUri()->__toString(), $reportPage2->getUri()->__toString());
        });

        return $report;
    }

    public function addDefaultUriFilter(string $defaultUriFilter): self
    {
        $this->defaultUriFilters[] = $defaultUriFilter;

        return $this;
    }

    public function setDefaultUriFilters(array $defaultUriFilters): self
    {
        $this->defaultUriFilters = [];
        foreach ($defaultUriFilters as $defaultUriFilter) {
            $this->addDefaultUriFilter($defaultUriFilter);
        }

        return $this;
    }
}
