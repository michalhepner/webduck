<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Bus\Command\AuditSiteCommand;
use Webduck\Bus\Handler\AuditSiteHandler;
use Webduck\Console\Helper\OptionHelper;
use Webduck\Console\Helper\ReportOutputHelper;
use Webduck\Console\Subscriber\AuditEventsSubscriber;
use Webduck\Dispatcher\DispatcherAwareInterface;
use Webduck\Dispatcher\DispatcherAwareTrait;
use Webduck\Domain\Audit\AuditCollection;
use Webduck\Domain\Audit\ResourceLoadAudit;
use Webduck\Domain\Audit\ViolationAudit;
use Webduck\Domain\Transformer\ReportPageToConsoleOutputTransformer;

class SiteAuditCommand extends ContainerAwareCommand implements DispatcherAwareInterface
{
    use DispatcherAwareTrait;

    protected function configure()
    {
        $this
            ->setName('site:audit')
            ->addArgument('url', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Url to be crawled')
            ->addOption('allowed-host', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('url-filter', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Allows to filter out crawled URLs based on regular expressions.')
        ;

        OptionHelper::addAllOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = $this->getContainer();

        $auditEventsSubscriber = new AuditEventsSubscriber($output, $container->get(ReportPageToConsoleOutputTransformer::class));
        $this->dispatcher && $this->dispatcher->addSubscriber($auditEventsSubscriber);

        $audits = $container->get(AuditCollection::class)->excludeMultiple(array_filter([
            !$input->getOption(OptionHelper::OPTION_AUDIT_RESOURCE_LOAD) ? ResourceLoadAudit::NAME : null,
            !$input->getOption(OptionHelper::OPTION_AUDIT_VIOLATIONS) ? ViolationAudit::NAME : null,
        ]));

        $command = AuditSiteCommand::create($input->getArgument('url'), $audits);
        $command->setUriFilters($input->getOption('url-filter'));
        $command->setAllowedHosts($input->getOption('allowed-host'));
        if (!empty($input->getOption(OptionHelper::OPTION_USERNAME)) && !empty($input->getOption(OptionHelper::OPTION_PASSWORD))) {
            $command->setUsername($input->getOption(OptionHelper::OPTION_USERNAME));
            $command->setPassword($input->getOption(OptionHelper::OPTION_PASSWORD));
        }
        $command->setShouldGenerateScreenshot((bool) $input->getOption(OptionHelper::OPTION_SAVE_SCREENSHOT));

        $report = $container->get(AuditSiteHandler::class)->handle($command);
        $container->get(ReportOutputHelper::class)->render($report, $input->getOption(OptionHelper::OPTION_OUTPUT), $output);

        $this->dispatcher && $this->dispatcher->removeSubscriber($auditEventsSubscriber);

        return 0;
    }
}
