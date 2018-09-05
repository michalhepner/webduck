<?php

declare(strict_types = 1);

namespace Webduck\Bus\Processor;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Webduck\Bus\Command\AuditPageCommand;
use Webduck\Bus\Handler\AuditPageHandler;
use Webduck\Domain\Storage\ReportStorage;

class AuditPageProcessor implements PsrProcessor, CommandSubscriberInterface
{
    /**
     * @var AuditPageHandler
     */
    protected $handler;

    /**
     * @var ReportStorage
     */
    protected $reportStorage;

    public function __construct(AuditPageHandler $handler, ReportStorage $reportStorage)
    {
        $this->handler = $handler;
        $this->reportStorage = $reportStorage;
    }

    public static function getSubscribedCommand()
    {
        return 'audit_page';
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        $command = AuditPageCommand::fromArray(JSON::decode($message->getBody()));
        $report = $this->handler->handle($command);

        $this->reportStorage->store($command->getUuid(), $report);

        return self::ACK;
    }
}
