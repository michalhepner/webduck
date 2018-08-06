<?php

declare(strict_types = 1);

namespace Webduck\Audit;

use Webduck\Provider\Event;
use Webduck\Provider\UrlData;
use Webduck\Util\ByteUtil;

class PageSizeAudit implements AuditInterface
{
    const NAME = 'Page size';

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

        $sum = 0;
        $weights = [];
        foreach ($requestEvents as $requestEvent) {
            foreach ($loadingEvents as $loadingEvent) {
                if ($requestEvent['requestId'] === $loadingEvent['requestId']) {
                    $weights[] = [
                        'url' => $requestEvent['request']['url'],
                        'length' => $loadingEvent['encodedDataLength'],
                    ];
                    $sum += $loadingEvent['encodedDataLength'];
                }
            }
        }

        if ($sum > $this->threshold) {
            $message = sprintf('Page transferred %s of data', ByteUtil::format($sum));
            $results->add(AuditResult::createWarning(self::NAME, $message, $weights));
        }

        return $results;
    }
}
