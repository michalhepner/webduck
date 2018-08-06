<?php

declare(strict_types = 1);

namespace Webduck\Audit;

use Webduck\Provider\Event;
use Webduck\Provider\UrlData;
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

    public function execute(UrlData $urlData): AuditResultCollection
    {
        $results = new AuditResultCollection();

        $loadingEvents = $urlData->getEvents()->filter(function (Event $event) {
            return $event->getName() === 'Network.loadingFinished';
        });

        $requestEvents = $urlData->getEvents()->filter(function (Event $event) {
            return $event->getName() === 'Network.requestWillBeSent';
        });

        foreach ($loadingEvents as $loadingEvent) {
            if ($loadingEvent->getData()['encodedDataLength'] > $this->threshold) {
                foreach ($requestEvents as $requestEvent) {
                    if ($requestEvent->getData()['requestId'] === $loadingEvent->getData()['requestId']) {
                        $message = sprintf('%s %s', $requestEvent['request']['url'], ByteUtil::format($loadingEvent['encodedDataLength']));
                        $results->add(AuditResult::createWarning(self::NAME, $message, $requestEvent->getData()));
                    }
                }
            }
        }

        return $results;
    }
}
