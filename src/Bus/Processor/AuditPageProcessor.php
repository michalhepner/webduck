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
use Webduck\Bus\Subscriber\ReportRequestProgressSubscriber;
use Webduck\Dispatcher\DispatcherAwareInterface;
use Webduck\Dispatcher\DispatcherAwareTrait;
use Webduck\Domain\Model\ReportRequest;
use Webduck\Domain\Storage\ReportRequestStorage;
use Webduck\Domain\Storage\ReportStorage;

class AuditPageProcessor implements PsrProcessor, CommandSubscriberInterface, DispatcherAwareInterface
{
    use DispatcherAwareTrait;

    /**
     * @var AuditPageHandler
     */
    protected $handler;

    /**
     * @var ReportStorage
     */
    protected $reportStorage;

    /**
     * @var ReportRequestStorage
     */
    protected $reportRequestStorage;

    public function __construct(AuditPageHandler $handler, ReportStorage $reportStorage, ReportRequestStorage $reportRequestStorage)
    {
        $this->handler = $handler;
        $this->reportStorage = $reportStorage;
        $this->reportRequestStorage = $reportRequestStorage;
    }

    public static function getSubscribedCommand()
    {
        return 'audit_page';
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        $command = AuditPageCommand::fromArray(JSON::decode($message->getBody()));

        $reportRequest = new ReportRequest($command->getUuid(), ReportRequest::STATUS_RUNNING, 0.0);
        $reportRequestProgressSubscriber = new ReportRequestProgressSubscriber($reportRequest, $this->reportRequestStorage);
        $this->dispatcher->addSubscriber($reportRequestProgressSubscriber);

        $report = $this->handler->handle($command);
        $this->reportStorage->store($report);

        $reportRequest->setStatus(ReportRequest::STATUS_FINISHED);
        $this->reportRequestStorage->store($reportRequest);
        $this->dispatcher->removeSubscriber($reportRequestProgressSubscriber);

        return self::ACK;
    }
}
