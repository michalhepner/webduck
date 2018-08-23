<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Siteqa\Test\Domain\Model\Uri;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Audit\AuditResultCollection;
use Webduck\Audit\AuditInterface;
use Webduck\Console\Helper\AuditResultHelper;
use Webduck\Provider\DataBundleProvider;
use Webduck\Provider\UrlData;

class PageAuditCommand extends AbstractAuditCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('page:audit')
            ->addArgument('url', InputArgument::IS_ARRAY | InputArgument::REQUIRED)
            ->addOption('save-html', null, InputOption::VALUE_REQUIRED, 'Where to save the output as HTML.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Uri[] $urls */
        $urls = array_map(Uri::class.'::createFromString', $input->getArgument('url'));
        $providerBin = $this->getContainer()->getParameter('bin.provider');
        $provider = new DataBundleProvider($providerBin, array_map(function (Uri $uri) { return $uri->__toString(); }, $urls));
        $urlDataCollection = $provider->provide()->getUrlDataCollection();

        $audits = $this->getAudits($input);

        $resultAudits = [];
        $screenshots = [];

        $urlIndex = 0;
        /** @var UrlData $urlData */
        foreach ($urlDataCollection as $urlData) {
            $urlIndex++;
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

        ksort($resultAudits);

        if ($htmlPath = $input->getOption('save-html')) {
            $twig = $this->getContainer()->get('twig');
            $auditHtml = $twig->render('audit.html.twig', [
                'site' => $urls[0]->getHost(),
                'audits' => $resultAudits,
                'screenshots' => $screenshots,
            ]);

            file_put_contents($htmlPath, $auditHtml);
        }

        return 0;
    }
}
