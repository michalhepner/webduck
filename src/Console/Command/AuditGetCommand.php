<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Console\Helper\OptionHelper;
use Webduck\Console\Helper\ReportOutputHelper;
use Webduck\Domain\Storage\ReportRequestStorage;
use Webduck\Domain\Storage\ReportStorage;

class AuditGetCommand extends ContainerAwareCommand
{
    /**
     * @var ReportStorage
     */
    protected $reportStorage;

    /**
     * @var ReportRequestStorage
     */
    protected $reportRequestStorage;

    /**
     * @var ReportOutputHelper
     */
    protected $reportOutputHelper;

    public function __construct(ReportStorage $reportStorage, ReportRequestStorage $reportRequestStorage, ReportOutputHelper $reportOutputHelper)
    {
        parent::__construct(null);

        $this->reportStorage = $reportStorage;
        $this->reportRequestStorage = $reportRequestStorage;
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
        $uuid = $input->getArgument('uuid');

        if ($this->reportStorage->exists($uuid)) {
            /** @var ReportStorage $reportStorage */
            $report = $this->reportStorage->get($input->getArgument('uuid'));
            $this->reportOutputHelper->render($report, $input->getOption(OptionHelper::OPTION_OUTPUT), $output);
        } else {
            if ($this->reportRequestStorage->exists($uuid)) {
                $reportRequest = $this->reportRequestStorage->get($uuid);
                $output->writeln(sprintf(
                    'The requested report is in state \'%s\' with progress %d%%',
                    $reportRequest->getStatusString(),
                    $reportRequest->getProgress() * 100
                ));
            } else {
                $output->writeln(sprintf('Requested UUID was not found'));
            }
            
            return 1;
        }

        return 0;
    }
}
