<?php

declare(strict_types = 1);

namespace Webduck\Domain\Audit;

use Webduck\Domain\Collection\InsightCollection;
use Webduck\Domain\Model\Browse;
use Webduck\Domain\Model\BrowseEvent;
use Webduck\Domain\Model\Insight;
use Webduck\Util\ByteUtil;

class ResourceSizeAudit implements AuditInterface
{
    const NAME = 'Resource size';

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
            if ($loadingEvent->getData()['encodedDataLength'] > $this->threshold) {
                foreach ($requestEvents as $requestEvent) {
                    if ($requestEvent->getData()['requestId'] === $loadingEvent->getData()['requestId']) {
                        $message = sprintf('%s %s', $requestEvent['request']['url'], ByteUtil::format($loadingEvent['encodedDataLength']));
                        $results->add(Insight::createWarning(self::NAME, $message, $requestEvent->getData()));
                    }
                }
            }
        }

        return $results;
    }
}
