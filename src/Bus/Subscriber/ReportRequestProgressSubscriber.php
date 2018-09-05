<?php

declare(strict_types = 1);

namespace Webduck\Bus\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webduck\Bus\Event\ReportPageEmittedEvent;
use Webduck\Bus\Event\UriQueuedEvent;
use Webduck\Domain\Model\ReportRequest;
use Webduck\Domain\Storage\ReportRequestStorage;

class ReportRequestProgressSubscriber implements EventSubscriberInterface
{
    /**
     * @var ReportRequest
     */
    protected $reportRequest;

    /**
     * @var ReportRequestStorage
     */
    protected $reportRequestStorage;

    /**
     * @var int
     */
    protected $urisCount = 0;

    /**
     * @var int
     */
    protected $urisFinished = 0;

    public function __construct(ReportRequest $reportRequest, ReportRequestStorage $reportRequestStorage)
    {
        $this->reportRequest = $reportRequest;
        $this->reportRequestStorage = $reportRequestStorage;
    }

    public static function getSubscribedEvents()
    {
        return [
            UriQueuedEvent::NAME => 'onUriQueued',
            ReportPageEmittedEvent::NAME => 'onReportPageEmitted',
        ];
    }

    public function onUriQueued(UriQueuedEvent $event)
    {
        $this->urisCount++;
    }

    public function onReportPageEmitted(ReportPageEmittedEvent $event)
    {
        $this->urisFinished++;

        $this->reportRequest->setProgress(round($this->urisFinished / $this->urisCount, 2));
        $this->reportRequestStorage->store($this->reportRequest);
    }
}
