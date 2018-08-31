<?php

namespace Webduck\Bus\Handler;

use GuzzleHttp\Client;
use Siteqa\Test\Domain\Model\HttpSuite;
use Siteqa\Test\Event\CrawlerUriQueuedEvent;
use Siteqa\Test\Event\SymfonyEventDispatcher;
use Siteqa\Test\Provider\CrawlerResultProvider;
use Siteqa\Test\Provider\HttpSuiteProvider;
use Siteqa\Test\Domain\Model\Uri as SiteqaUri;
use Webduck\Bus\Command\AuditSiteCommand;
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

class AuditSiteHandler implements DispatcherAwareInterface
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

    public function handle(AuditSiteCommand $command): Report
    {
        $crawlerUris = $command->getUris();

        $command->hasUserInfo() && $crawlerUris->walk(function (Uri &$uri) use ($command) {
            $uri = $uri->withUserInfo($command->getUsername(), $command->getPassword());
        });

        $crawler = $this->getCrawler();

        foreach ($command->getUriFilters()->meld($this->defaultUriFilters) as $uriFilter) {
            $crawler->addUriFilter(function (SiteqaUri $uri) use ($uriFilter) {
                return !preg_match('/'.$uriFilter.'/i', $uri->__toString());
            });
        }

        $allowedHosts = $command->getAllowedHosts()->copy();
        /** @var Uri $crawlerUri */
        foreach ($crawlerUris as $crawlerUri) {
            $allowedHosts->add($crawlerUri->getHost());
        }

        $allowedHosts = $allowedHosts->unique();

        $crawlerResult = $crawler->provide(
            $crawlerUris->map(function (Uri $uri) {
                return $uri->__toString();
            }),
            $allowedHosts->getArrayCopy()
        );

        $browseUris = new UriCollection();
        /** @var HttpSuite $httpSuite */
        foreach ($crawlerResult->getHttpSuites() as $httpSuite) {
            $browseUris->add($httpSuite->getUri()->__toString());
        }

        $browseUris = $browseUris->unique();

        $report = new Report(sprintf('Site report for: %s', implode(', ', $allowedHosts->getArrayCopy())));

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

    protected function getCrawler(): CrawlerResultProvider
    {
        $httpSuiteProvider = new HttpSuiteProvider(new Client());
        $crawler = new CrawlerResultProvider($httpSuiteProvider);
        $crawler->setRecursive(true);

        if ($this->dispatcher) {
            $dispatcher = new SymfonyEventDispatcher($this->dispatcher);
            $httpSuiteProvider->setEventDispatcher($dispatcher);
            $crawler->setEventDispatcher($dispatcher);
            $dispatcher->addListener(CrawlerUriQueuedEvent::NAME, function ($event) {
                /** @var CrawlerUriQueuedEvent $event */
                $this->dispatch(UriQueuedEvent::NAME, new UriQueuedEvent(Uri::createFromString($event->getUri()->__toString())));
            });
        }

        return $crawler;
    }
}
