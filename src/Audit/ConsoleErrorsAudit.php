<?php

declare(strict_types = 1);

namespace Webduck\Audit;

use Webduck\Provider\Event;
use Webduck\Provider\UrlData;

class ConsoleErrorsAudit implements AuditInterface
{
    const NAME = 'Console';

    public function getName(): string
    {
        return self::NAME;
    }

    public function execute(UrlData $urlData): AuditResultCollection
    {
        $results = new AuditResultCollection();
        $events = $urlData->getEvents()->filter(function (Event $event) {
            return $event->getName() === 'Log.entryAdded'
                && !in_array($event->getData()['entry']['source'], ['security', 'violation'], true)
            ;
        });

        foreach ($events as $event) {
            $data = $event->getData()['entry'];
            foreach (['text', 'source', 'level', 'timestamp'] as $key) {
                if (array_key_exists($key, $data)) {
                    unset($data[$key]);
                }
            }

            $message = $event->getData()['entry']['text'];
            $results->add(AuditResult::createWarning(self::NAME, $message, $data));
        }

        return $results;
    }
}
