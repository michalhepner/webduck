<?php

declare(strict_types = 1);

namespace Webduck\Domain\Audit;

use Webduck\Domain\Collection\InsightCollection;
use Webduck\Domain\Model\Browse;
use Webduck\Domain\Model\BrowseEvent;
use Webduck\Domain\Model\Insight;
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

    public function execute(Browse $urlData): InsightCollection
    {
        $results = new InsightCollection();

        $loadingEvents = $urlData->getEvents()->filter(function (BrowseEvent $event) {
            return $event->getName() === 'Network.loadingFinished';
        });

        $requestEvents = $urlData->getEvents()->filter(function (BrowseEvent $event) {
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
            $results->add(Insight::createWarning(self::NAME, $message, $weights));
        }

        return $results;
    }

    public function unserialize($serialized)
    {
        $this->threshold = unserialize($serialized)['threshold'];
    }

    public function serialize()
    {
        return serialize([
            'threshold' => $this->threshold
        ]);
    }
}
