<?php

declare(strict_types = 1);

namespace Webduck\Domain\Audit;

use Webduck\Domain\Collection\InsightCollection;
use Webduck\Domain\Model\Browse;
use Webduck\Domain\Model\BrowseEvent;
use Webduck\Domain\Model\Insight;

class ConsoleErrorsAudit implements AuditInterface
{
    const NAME = 'Console';

    public function getName(): string
    {
        return self::NAME;
    }

    public function execute(Browse $browse): InsightCollection
    {
        $results = new InsightCollection();
        $events = $browse->getEvents()->filter(function (BrowseEvent $event) {
            return $event->getName() === 'Log.entryAdded'
                && !in_array($event->getData()['entry']['source'], ['security', 'violation'], true)
            ;
        });

        /** @var BrowseEvent $event */
        foreach ($events as $event) {
            $data = $event->getData()['entry'];
            foreach (['text', 'source', 'level', 'timestamp'] as $key) {
                if (array_key_exists($key, $data)) {
                    unset($data[$key]);
                }
            }

            $message = $event->getData()['entry']['text'];
            $results->add(Insight::createWarning(self::NAME, $message, $data));
        }

        return $results;
    }
}
