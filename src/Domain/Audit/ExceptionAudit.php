<?php

declare(strict_types = 1);

namespace Webduck\Domain\Audit;

use Webduck\Domain\Collection\InsightCollection;
use Webduck\Domain\Model\Browse;
use Webduck\Domain\Model\BrowseEvent;
use Webduck\Domain\Model\Insight;

class ExceptionAudit implements AuditInterface
{
    const NAME = 'Runtime exception';

    public function getName(): string
    {
        return self::NAME;
    }

    public function execute(Browse $urlData): InsightCollection
    {
        $results = new InsightCollection();
        $events = $urlData->getEvents()->filter(function (BrowseEvent $event) {
            return $event->getName() === 'Runtime.exceptionThrown';
        });

        /** @var BrowseEvent $event */
        foreach ($events as $event) {
            $message = implode(' ', array_map(
                'trim',
                explode(
                    PHP_EOL,
                    $event->getData()['exceptionDetails']['exception']['description']
                )
            ));
            $data = $event->getData()['exceptionDetails'];
            $results->add(Insight::createError(self::NAME, $message, $data));
        }

        return $results;
    }

    public function unserialize($serialized)
    {
    }

    public function serialize()
    {
        return serialize(null);
    }
}
