<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Console\Helper\OptionHelper;
use Webduck\Console\Helper\ReportOutputHelper;
use Webduck\Domain\Storage\ReportStorage;

class AuditGetCommand extends ContainerAwareCommand
{
    /**
     * @var ReportStorage
     */
    protected $reportStorage;

    /**
     * @var ReportOutputHelper
     */
    protected $reportOutputHelper;

    public function __construct(ReportStorage $reportStorage, ReportOutputHelper $reportOutputHelper)
    {
        parent::__construct(null);

        $this->reportStorage = $reportStorage;
        $this->reportOutputHelper = $reportOutputHelper;
    }

    protected function configure()
    {
        $this
            ->setName('audit:get')
            ->addArgument('uuid', InputArgument::REQUIRED)
        ;

        OptionHelper::addOutputOption($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ReportStorage $reportStorage */
        $report = $this->reportStorage->get($input->getArgument('uuid'));
        $this->reportOutputHelper->render($report, $input->getOption(OptionHelper::OPTION_OUTPUT), $output);
    }
}
