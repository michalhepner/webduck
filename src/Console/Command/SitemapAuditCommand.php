<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Bus\Command\AuditSitemapCommand;
use Webduck\Bus\Handler\AuditSitemapHandler;

class SitemapAuditCommand extends AbstractAuditCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('sitemap:audit')
            ->addArgument('sitemap-url', InputArgument::REQUIRED)
            ->addOption('url-filter', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Allows to filter out crawled URLs based on regular expressions.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->attachListeners($output);

        $audits = $this->getAudits($input);
        $command = AuditSitemapCommand::create($input->getArgument('sitemap-url'), $audits);

        if (!empty($input->getOption('username')) && !empty($input->getOption('password'))) {
            $command->setUsername($input->getOption('username'));
            $command->setPassword($input->getOption('password'));
        }

        $command->setUriFilters($input->getOption('url-filter'));

        $report = $this->getContainer()->get(AuditSitemapHandler::class)->handle($command);

        $this->outputReport($report, $input, $output);

        return 0;
    }
}
