<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Webduck\Audit\AuditCollection;
use Webduck\Audit\ConsoleErrorsAudit;
use Webduck\Audit\ExceptionAudit;
use Webduck\Audit\PageSizeAudit;
use Webduck\Audit\ResourceLoadAudit;
use Webduck\Audit\ResourceSizeAudit;
use Webduck\Audit\SecurityAudit;
use Webduck\Audit\ViolationAudit;

abstract class AbstractAuditCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->addOption('audit-violations', null, InputOption::VALUE_NONE)
            ->addOption('audit-resource-load', null, InputOption::VALUE_NONE)
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED)
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED)
        ;
    }

    protected function getAudits(InputInterface $input): AuditCollection
    {
        return new AuditCollection(array_filter([
            new ConsoleErrorsAudit(),
            new ExceptionAudit(),
            new PageSizeAudit(5 * 1024 * 1024),
            $input->getOption('audit-resource-load') ? new ResourceLoadAudit(1000) : null,
            new ResourceSizeAudit(1024 * 1024),
            new SecurityAudit(),
            $input->getOption('audit-violations') ? new ViolationAudit() : null,
        ]));
    }
}
