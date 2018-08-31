<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Bus\Command\AuditSiteCommand;
use Webduck\Bus\Handler\AuditSiteHandler;

class SiteAuditCommand extends AbstractAuditCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('site:audit')
            ->addArgument('url', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Url to be crawled')
            ->addOption('allowed-host', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('url-filter', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Allows to filter out crawled URLs based on regular expressions.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->attachListeners($output);

        $audits = $this->getAudits($input);
        $command = AuditSiteCommand::create($input->getArgument('url'), $audits);

        if (!empty($input->getOption('username')) && !empty($input->getOption('password'))) {
            $command->setUsername($input->getOption('username'));
            $command->setPassword($input->getOption('password'));
        }

        $command->setUriFilters($input->getOption('url-filter'));
        $command->setAllowedHosts($input->getOption('allowed-host'));

        $report = $this->getContainer()->get(AuditSiteHandler::class)->handle($command);

        $this->outputReport($report, $input, $output);

        return 0;
    }
}
