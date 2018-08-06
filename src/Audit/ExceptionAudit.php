<?php

declare(strict_types = 1);

namespace Webduck\Audit;

use Webduck\Provider\Event;
use Webduck\Provider\UrlData;

class ExceptionAudit implements AuditInterface
{
    const NAME = 'Runtime exception';

    public function getName(): string
    {
        return self::NAME;
    }

    public function execute(UrlData $urlData): AuditResultCollection
    {
        $results = new AuditResultCollection();
        $events = $urlData->getEvents()->filter(function (Event $event) {
            return $event->getName() === 'Runtime.exceptionThrown';
        });

        foreach ($events as $event) {
            $message = implode(' ', array_map(
                'trim',
                explode(
                    PHP_EOL,
                    $event->getData()['exceptionDetails']['exception']['description']
                )
            ));
            $data = $event->getData()['exceptionDetails'];
            $results->add(AuditResult::createError(self::NAME, $message, $data));
        }

        return $results;
    }
}
