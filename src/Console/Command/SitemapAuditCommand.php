<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use GuzzleHttp\Client;
use Siteqa\Test\Domain\Model\Sitemap;
use Siteqa\Test\Domain\Model\Uri;
use Siteqa\Test\Provider\HttpSuiteProvider;
use Siteqa\Test\Provider\SitemapResultProvider;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Audit\AuditInterface;
use Webduck\Audit\AuditResultCollection;
use Webduck\Console\Helper\AuditResultHelper;
use Webduck\Provider\DataBundle;
use Webduck\Provider\DataBundleProvider;
use Webduck\Provider\UrlData;

class SitemapAuditCommand extends AbstractAuditCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('sitemap:audit')
            ->addArgument('sitemap-url', InputArgument::REQUIRED)
            ->addOption('pool-size', null, InputOption::VALUE_REQUIRED, 'How many parallel calls should be handled a the same time?', 5)
            ->addOption('save-html', null, InputOption::VALUE_REQUIRED, 'Where to save the output as HTML.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sitemapUrl = Uri::createFromString($input->getArgument('sitemap-url'));
        $sitemapProvider = new SitemapResultProvider(new HttpSuiteProvider(new Client()));
        $sitemap = new Sitemap($sitemapUrl);
        $sitemapResult = $sitemapProvider->provide($sitemap);

        $urls = [];
        /** @var Uri $uri */
        foreach ($sitemapResult->getUris() as $uri) {
            $uri = !$uri->hasHost() ? $sitemapUrl->getHost() : $uri;
            $uri = empty($uri->getScheme()) ? $uri->withScheme($sitemapUrl->getScheme()) : $uri;

            $urls[] = $uri->__toString();
        }

        $urls = array_unique($urls);

        $providerBin = $this->getContainer()->getParameter('bin.provider');
        $audits = $this->getAudits($input);

        $urlIndex = 0;

        $resultAudits = [];
        $screenshots = [];

        (new DataBundleProvider($providerBin, $urls))
            ->setPoolSize($input->getOption('pool-size'))
            ->setUser($input->getOption('user'))
            ->setPassword($input->getOption('password'))
            ->emit(function (DataBundle $bundle) use ($output, $audits, $urls, &$urlIndex, &$resultAudits, &$screenshots) {
                $urlIndex++;

                /** @var UrlData $urlData */
                foreach ($bundle->getUrlDataCollection() as $urlData) {
                    /** @var AuditInterface $audit */
                    $auditResultsArr = [];
                    foreach ($audits as $audit) {
                        $auditResultsArr[] = $audit->execute($urlData);
                    }

                    /** @var AuditResultCollection $auditResults */
                    $auditResults = call_user_func_array(AuditResultCollection::class.'::merge', $auditResultsArr);

                    $output->writeln(sprintf('<comment>[%d/%d] %s</comment>', $urlIndex, count($urls), $urlData->getUrl()));
                    $output->writeln('<comment>------------------------------------------------------------</comment>');

                    $helper = new AuditResultHelper($output, $urlData->getUrl(), $auditResults);
                    $helper->render();

                    $resultAudits[$urlData->getUrl()] = $auditResults;
                    $screenshots[$urlData->getUrl()] = $urlData->getScreenshot();
                }
            })
        ;

        ksort($resultAudits);

        if ($htmlPath = $input->getOption('save-html')) {
            $twig = $this->getContainer()->get('twig');
            $auditHtml = $twig->render('audit.html.twig', [
                'site' => $sitemapUrl->getHost(),
                'audits' => $resultAudits,
                'screenshots' => $screenshots
            ]);

            file_put_contents($htmlPath, $auditHtml);
        }

        return 0;
    }
}
