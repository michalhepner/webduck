<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Bus\Command\AuditPageCommand;
use Webduck\Bus\Handler\AuditPageHandler;

class PageAuditCommand extends AbstractAuditCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('page:audit')
            ->addArgument('url', InputArgument::IS_ARRAY | InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->attachListeners($output);

        $audits = $this->getAudits($input);
        $command = AuditPageCommand::create($input->getArgument('url'), $audits);

        if (!empty($input->getOption('username')) && !empty($input->getOption('password'))) {
            $command->setUsername($input->getOption('username'));
            $command->setPassword($input->getOption('password'));
        }

        $report = $this->getContainer()->get(AuditPageHandler::class)->handle($command);

        $this->outputReport($report, $input, $output);

        return 0;
    }
}
