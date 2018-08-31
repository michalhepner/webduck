<?php

declare(strict_types = 1);

namespace Webduck\Domain\Audit;

use Webduck\Domain\Collection\InsightCollection;
use Webduck\Domain\Model\Browse;
use Webduck\Domain\Model\BrowseEvent;
use Webduck\Domain\Model\Insight;

class ResourceLoadAudit implements AuditInterface
{
    const NAME = 'Resource load';

    /**
     * @var int
     */
    protected $threshold;

    public function __construct(int $threshold)
    {
        $this->threshold = $threshold;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function execute(Browse $urlData): InsightCollection
    {
        $results = new InsightCollection();

        $loadingEvents = $urlData->getEvents()->filter(function (BrowseEvent $event) {
            return $event->getName() === 'Network.loadingFinished';
        });

        $requestEvents = $urlData->getEvents()->filter(function (BrowseEvent $event) {
            return $event->getName() === 'Network.requestWillBeSent';
        });

        foreach ($loadingEvents as $loadingEvent) {
            foreach ($requestEvents as $requestEvent) {
                if ($requestEvent['requestId'] === $loadingEvent['requestId']) {
                    $duration = (int) (($loadingEvent['timestamp'] - $requestEvent['timestamp']) * 1000);
                    if ($duration > $this->threshold) {
                        $message = sprintf('%s %s ms', $requestEvent['request']['url'], $duration);
                        $results->add(Insight::createWarning(self::NAME, $message, $requestEvent->getData()));
                    }
                }
            }
        }

        return $results;
    }
}
