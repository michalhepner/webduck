<?php

declare(strict_types = 1);

namespace Webduck\Bus\Processor;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Webduck\Bus\Command\AuditSitemapCommand;
use Webduck\Bus\Handler\AuditSitemapHandler;
use Webduck\Domain\Storage\ReportStorage;

class AuditSitemapProcessor implements PsrProcessor, CommandSubscriberInterface
{
    /**
     * @var AuditSitemapHandler
     */
    protected $handler;

    /**
     * @var ReportStorage
     */
    protected $reportStorage;

    public function __construct(AuditSitemapHandler $handler, ReportStorage $reportStorage)
    {
        $this->handler = $handler;
        $this->reportStorage = $reportStorage;
    }

    public static function getSubscribedCommand()
    {
        return 'audit_sitemap';
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        $command = AuditSitemapCommand::fromArray(JSON::decode($message->getBody()));
        $report = $this->handler->handle($command);

        $this->reportStorage->store($command->getUuid(), $report);

        return self::ACK;
    }
}
