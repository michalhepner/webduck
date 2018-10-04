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
use Webduck\Bus\Event\UriQueuedEvent;
use Webduck\Domain\Collection\StringCollection;
use Webduck\Domain\Collection\UriCollection;
use Webduck\Domain\Model\Report;
use Webduck\Domain\Model\Uri;

class AuditSiteHandler extends AbstractAuditHandler
{
    /**
     * @var string[]
     */
    protected $defaultUriFilters = [];

    public function handle(AuditSiteCommand $command): Report
    {
        $browseUris = $this->getUris($command);

        $allHosts = (new StringCollection($browseUris->map(function (Uri $uri) {
            return $uri->getHost();
        })));

        $allowedHosts = $allHosts->unique()->getArrayCopy();

        return $this->prepareReport(
            $command->getUuid(),
            sprintf('Site report for: %s', implode(', ', $allowedHosts)),
            $browseUris,
            $command->getAudits(),
            $command->getShouldGenerateScreenshot(),
            $command->getUsername(),
            $command->getPassword()
        );
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

    protected function getUris(AuditSiteCommand $command): UriCollection
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

        return $browseUris->unique();
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
