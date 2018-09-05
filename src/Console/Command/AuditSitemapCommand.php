<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Enqueue\Client\ProducerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Bus\Command\AuditSitemapCommand as BusAuditSitemapCommand;
use Webduck\Bus\Handler\AuditSitemapHandler;
use Webduck\Bus\Processor\AuditSitemapProcessor;
use Webduck\Console\Helper\OptionHelper;
use Webduck\Console\Helper\ReportOutputHelper;
use Webduck\Console\Subscriber\AuditEventsSubscriber;
use Webduck\Dispatcher\DispatcherAwareInterface;
use Webduck\Dispatcher\DispatcherAwareTrait;
use Webduck\Domain\Audit\AuditCollection;
use Webduck\Domain\Audit\ResourceLoadAudit;
use Webduck\Domain\Audit\ViolationAudit;
use Webduck\Domain\Model\ReportRequest;
use Webduck\Domain\Storage\ReportRequestStorage;
use Webduck\Domain\Transformer\ReportPageToConsoleOutputTransformer;

class AuditSitemapCommand extends ContainerAwareCommand implements DispatcherAwareInterface
{
    use DispatcherAwareTrait;

    protected function configure()
    {
        $this
            ->setName('audit:sitemap')
            ->addArgument('sitemap-url', InputArgument::REQUIRED)
            ->addOption('url-filter', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Allows to filter out crawled URLs based on regular expressions.')
            ->setAliases(['sitemap:audit'])
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
        ]));

        $command = BusAuditSitemapCommand::create($input->getArgument('sitemap-url'), $audits);
        $command->setUriFilters($input->getOption('url-filter'));
        if (!empty($input->getOption(OptionHelper::OPTION_USERNAME)) && !empty($input->getOption(OptionHelper::OPTION_PASSWORD))) {
            $command->setUsername($input->getOption(OptionHelper::OPTION_USERNAME));
            $command->setPassword($input->getOption(OptionHelper::OPTION_PASSWORD));
        }
        $command->setShouldGenerateScreenshot((bool) $input->getOption(OptionHelper::OPTION_SAVE_SCREENSHOT));

        if ($input->getOption(OptionHelper::OPTION_ASYNC)) {
            /** @var ProducerInterface $producer */
            $producer = $this->getContainer()->get(ProducerInterface::class);
            $producer->sendCommand(AuditSitemapProcessor::getSubscribedCommand(), $command->toArray());
            $container->get(ReportRequestStorage::class)->store(ReportRequest::create($command->getUuid()));
            $output->writeln($command->getUuid());
        } else {
            $report = $container->get(AuditSitemapHandler::class)->handle($command);
            $container->get(ReportOutputHelper::class)->render($report, $input->getOption(OptionHelper::OPTION_OUTPUT), $output);
        }

        $this->dispatcher && $auditEventsSubscriber !== null && $this->dispatcher->addSubscriber($auditEventsSubscriber);

        return 0;
    }
}
