<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use GuzzleHttp\Client;
use Siteqa\Test\Domain\Model\HttpSuite;
use Siteqa\Test\Domain\Model\Uri;
use Siteqa\Test\Event\CrawlerUriQueuedEvent;
use Siteqa\Test\Event\SymfonyEventDispatcher;
use Siteqa\Test\Provider\CrawlerResultProvider;
use Siteqa\Test\Provider\HttpSuiteProvider;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Environment;
use Webduck\Audit\AuditInterface;
use Webduck\Audit\AuditResultCollection;
use Webduck\Console\Helper\AuditResultHelper;
use Webduck\Provider\DataBundle;
use Webduck\Provider\DataBundleProvider;
use Webduck\Provider\UrlData;

class SiteAuditCommand extends AbstractAuditCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('site:audit')
            ->addArgument('url', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Url to be crawled')
            ->addOption('pool-size', null, InputOption::VALUE_REQUIRED, 'How many parallel calls should be handled a the same time?', 5)
            ->addOption('save-html', null, InputOption::VALUE_REQUIRED, 'Where to save the output as HTML.')
            ->addOption('allowed-host', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('url-filter', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Allows to filter out crawled URLs based on regular expressions.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dispatcher = new SymfonyEventDispatcher(new EventDispatcher());
        $dispatcher->addListener(CrawlerUriQueuedEvent::NAME, function ($event) use ($output) {
            $output->writeln(sprintf('<comment>Queued uri %s</comment>', $event->getUri()->__toString()));
        });

        /** @var Uri[] $crawlerUrls */
        $crawlerUrls = array_map(Uri::class.'::createFromString', $input->getArgument('url'));
        $allowedHosts = array_unique(array_map(function (Uri $uri) { return $uri->getHost(); }, $crawlerUrls));
        $httpSuiteProvider = new HttpSuiteProvider(new Client());
        $httpSuiteProvider->setEventDispatcher($dispatcher);
        $crawler = new CrawlerResultProvider($httpSuiteProvider);
        $crawler->setEventDispatcher($dispatcher);
        $crawler->setRecursive(true);

        $urlFilters = array_merge($input->getOption('url-filter'), [
            '.*\.(pdf|jpg|jpeg|gif|png)'
        ]);

        foreach ($urlFilters as $regex) {
            $crawler->addUriFilter(function (Uri $uri) use ($regex) {
                return !preg_match('/'.$regex.'/i', $uri->__toString());
            });
        }

        $crawlerResult = $crawler->provide($crawlerUrls, $allowedHosts);

        $urls = [];
        /** @var HttpSuite $httpSuite */
        foreach ($crawlerResult->getHttpSuites() as $httpSuite) {
            $urls[] = $httpSuite->getUri()->__toString();
        }

        $urls = array_unique($urls);

        $providerBin = $this->getContainer()->getParameter('bin.provider');
        $audits = $this->getAudits($input);

        $urlIndex = 0;

        $resultAudits = [];
        $screenshots = [];

        (new DataBundleProvider($providerBin, $urls))
            ->setPoolSize($input->getOption('pool-size'))
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
            /** @var Environment $twig */
            $twig = $this->getContainer()->get('twig');
            $auditHtml = $twig->render('audit.html.twig', [
                'site' => $crawlerUrls[0]->getHost(),
                'audits' => $resultAudits,
                'screenshots' => $screenshots,
            ]);

            file_put_contents($htmlPath, $auditHtml);
        }

        return 0;
    }
}
