<?php

declare(strict_types = 1);

namespace Webduck\Console\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Console\Helper\OptionHelper;
use Webduck\Console\Helper\ReportOutputHelper;
use Webduck\Domain\Model\ReportRequest;
use Webduck\Domain\Storage\ReportRequestStorage;
use Webduck\Domain\Storage\ReportStorage;

class AuditListCommand extends ContainerAwareCommand
{
    /**
     * @var ReportRequestStorage
     */
    protected $reportRequestStorage;

    public function __construct(ReportStorage $reportStorage, ReportRequestStorage $reportRequestStorage, ReportOutputHelper $reportOutputHelper)
    {
        parent::__construct(null);

        $this->reportRequestStorage = $reportRequestStorage;
    }

    protected function configure()
    {
        $this->setName('audit:list');

        OptionHelper::addOutputOption($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['uuid', 'status', 'progress']);

        /** @var ReportRequest $reportRequest */
        foreach ($this->reportRequestStorage->index() as $reportRequest) {
            $table->addRow([$reportRequest->getReportUuid(), $reportRequest->getStatusString(), $reportRequest->getProgress()]);
        }

        $table->render();

        return 0;
    }
}
