<?php

declare(strict_types = 1);

namespace Webduck\Console\Subscriber;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webduck\Bus\Event\ReportPageEmittedEvent;
use Webduck\Bus\Event\UriQueuedEvent;
use Webduck\Domain\Transformer\ReportPageToConsoleOutputTransformer;

class AuditEventsSubscriber implements EventSubscriberInterface
{
    /**
     * @var int
     */
    protected $uriCount = 0;

    /**
     * @var int
     */
    protected $uriIndex = 0;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ReportPageToConsoleOutputTransformer
     */
    protected $transformer;

    public function __construct(OutputInterface $output, ReportPageToConsoleOutputTransformer $transformer)
    {
        $this->output = $output;
        $this->transformer = $transformer;
    }

    public function onUriQueued(UriQueuedEvent $event)
    {
        $this->uriCount++;

        fwrite(STDERR, $this->output->getFormatter()->format(sprintf(
            "<comment>Queued URI %s</comment>\n",
            $event->getUri()->__toString()
        )));
    }

    public function onReportPageEmitted(ReportPageEmittedEvent $event)
    {
        $this->uriIndex++;

        fwrite(STDERR, $this->output->getFormatter()->format(sprintf(
            "%s\n",
            $this->transformer->transform(
                $event->getReportPage(),
                $this->output->isVerbose(),
                true,
                $this->uriIndex,
                $this->uriCount
            )
        )));
    }

    public static function getSubscribedEvents()
    {
        return [
            UriQueuedEvent::NAME => 'onUriQueued',
            ReportPageEmittedEvent::NAME => 'onReportPageEmitted',
        ];
    }
}
