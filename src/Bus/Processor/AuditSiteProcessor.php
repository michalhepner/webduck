<?php

declare(strict_types = 1);

namespace Webduck\Bus\Processor;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Webduck\Bus\Command\AuditSiteCommand;
use Webduck\Bus\Handler\AuditSiteHandler;
use Webduck\Domain\Storage\ReportStorage;

class AuditSiteProcessor implements PsrProcessor, CommandSubscriberInterface
{
    /**
     * @var AuditSiteHandler
     */
    protected $handler;

    /**
     * @var ReportStorage
     */
    protected $reportStorage;

    public function __construct(AuditSiteHandler $handler, ReportStorage $reportStorage)
    {
        $this->handler = $handler;
        $this->reportStorage = $reportStorage;
    }

    public static function getSubscribedCommand()
    {
        return 'audit_site';
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        $command = AuditSiteCommand::fromArray(JSON::decode($message->getBody()));
        $report = $this->handler->handle($command);

        $this->reportStorage->store($command->getUuid(), $report);

        return self::ACK;
    }
}
