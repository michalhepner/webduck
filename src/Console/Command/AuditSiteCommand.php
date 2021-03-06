<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Enqueue\Client\ProducerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Bus\Command\AuditSiteCommand as BusAuditSiteCommand;
use Webduck\Bus\Handler\AuditSiteHandler;
use Webduck\Bus\Processor\AuditSiteProcessor;
use Webduck\Console\Helper\OptionHelper;
use Webduck\Console\Helper\ReportOutputHelper;
use Webduck\Console\Subscriber\AuditEventsSubscriber;
use Webduck\Dispatcher\DispatcherAwareInterface;
use Webduck\Dispatcher\DispatcherAwareTrait;
use Webduck\Domain\Audit\AuditCollection;
use Webduck\Domain\Audit\HtmlAudit;
use Webduck\Domain\Audit\ResourceLoadAudit;
use Webduck\Domain\Audit\ViolationAudit;
use Webduck\Domain\Model\ReportRequest;
use Webduck\Domain\Storage\ReportRequestStorage;
use Webduck\Domain\Transformer\ReportPageToConsoleOutputTransformer;

class AuditSiteCommand extends ContainerAwareCommand implements DispatcherAwareInterface
{
    use DispatcherAwareTrait;

    protected function configure()
    {
        $this
            ->setName('audit:site')
            ->addArgument('url', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Url to be crawled')
            ->addOption('allowed-host', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('url-filter', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Allows to filter out crawled URLs based on regular expressions.')
            ->setAliases(['site:audit'])
        ;

        OptionHelper::addAllOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = $this->getContainer();

        $auditEventsSubscriber = null;

        if (!$input->getOption(OptionHelper::OPTION_ASYNC)) {
            $auditEventsSubscriber = new AuditEventsSubscriber($output, $container->get(ReportPageToConsoleOutputTransformer::class));
        }

        $this->dispatcher && $auditEventsSubscriber !== null && $this->dispatcher->addSubscriber($auditEventsSubscriber);

        $audits = $container->get(AuditCollection::class)->excludeMultiple(array_filter([
            !$input->getOption(OptionHelper::OPTION_AUDIT_RESOURCE_LOAD) ? ResourceLoadAudit::NAME : null,
            !$input->getOption(OptionHelper::OPTION_AUDIT_VIOLATIONS) ? ViolationAudit::NAME : null,
            !$input->getOption(OptionHelper::OPTION_AUDIT_HTML) ? HtmlAudit::NAME : null,
        ]));

        $command = BusAuditSiteCommand::create($input->getArgument('url'), $audits);
        $command->setUriFilters($input->getOption('url-filter'));
        $command->setAllowedHosts($input->getOption('allowed-host'));
        if (!empty($input->getOption(OptionHelper::OPTION_USERNAME)) && !empty($input->getOption(OptionHelper::OPTION_PASSWORD))) {
            $command->setUsername($input->getOption(OptionHelper::OPTION_USERNAME));
            $command->setPassword($input->getOption(OptionHelper::OPTION_PASSWORD));
        }
        $command->setShouldGenerateScreenshot((bool) $input->getOption(OptionHelper::OPTION_SAVE_SCREENSHOT));

        if ($input->getOption(OptionHelper::OPTION_ASYNC)) {
            /** @var ProducerInterface $producer */
            $producer = $this->getContainer()->get(ProducerInterface::class);
            $producer->sendCommand(AuditSiteProcessor::getSubscribedCommand(), $command->toArray());
            $container->get(ReportRequestStorage::class)->store(ReportRequest::create($command->getUuid()));
            $output->writeln($command->getUuid());
        } else {
            $report = $container->get(AuditSiteHandler::class)->handle($command);
            $container->get(ReportOutputHelper::class)->render($report, $input->getOption(OptionHelper::OPTION_OUTPUT), $output);
        }

        $this->dispatcher && $auditEventsSubscriber !== null && $this->dispatcher->removeSubscriber($auditEventsSubscriber);

        return 0;
    }
}
