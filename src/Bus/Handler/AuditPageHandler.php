<?php

namespace Webduck\Bus\Handler;

use Webduck\Bus\Command\AuditPageCommand;
use Webduck\Bus\Event\UriQueuedEvent;
use Webduck\Domain\Model\Report;
use Webduck\Domain\Model\Uri;

class AuditPageHandler extends AbstractAuditHandler
{
    public function handle(AuditPageCommand $command): Report
    {
        $browseUris = $command->getUris()->unique();

        foreach ($browseUris as $uri) {
            $this->dispatch(UriQueuedEvent::NAME, new UriQueuedEvent($uri));
        }

        $uriHosts = array_unique($command->getUris()->map(function (Uri $uri) {
            return $uri->getHost();
        }));

        return $this->prepareReport(
            $command->getUuid(),
            sprintf('Site report for: %s', implode(', ', $uriHosts)),
            $browseUris,
            $command->getAudits(),
            $command->getShouldGenerateScreenshot(),
            $command->getUsername(),
            $command->getPassword()
        );
    }
}
