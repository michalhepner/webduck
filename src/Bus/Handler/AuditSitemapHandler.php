<?php

namespace Webduck\Bus\Handler;

use GuzzleHttp\Client;
use Siteqa\Test\Domain\Model\Sitemap;
use Siteqa\Test\Provider\HttpSuiteProvider;
use Siteqa\Test\Domain\Model\Uri as SiteqaUri;
use Siteqa\Test\Provider\SitemapResultProvider;
use Webduck\Bus\Command\AuditSitemapCommand;
use Webduck\Bus\Event\UriQueuedEvent;
use Webduck\Domain\Collection\UriCollection;
use Webduck\Domain\Model\Report;
use Webduck\Domain\Model\Uri;

class AuditSitemapHandler extends AbstractAuditHandler
{
    /**
     * @var string[]
     */
    protected $defaultUriFilters = [];

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
            $uri = !$uri->hasHost() ? $uri->withHost($sitemapUrl->getHost()) : $uri;
            $uri = empty($uri->getScheme()) ? $uri->withScheme($sitemapUrl->getScheme()) : $uri;
            $urls[] = $uri->__toString();
        }

        $browseUris = new UriCollection(array_unique($urls));
        $browseUris = $browseUris->unique();

        foreach ($command->getUriFilters() as $uriFilter) {
            $browseUris = $browseUris->filter(function (Uri $uri) use ($uriFilter) {
                return !preg_match('/'.$uriFilter.'/i', $uri->__toString());
            });
        }

        foreach ($browseUris as $uri) {
            $this->dispatch(UriQueuedEvent::NAME, new UriQueuedEvent($uri));
        }

        return $this->prepareReport(
            $command->getUuid(),
            sprintf('Site report for: %s', $sitemapUrl->getHost()),
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
}
