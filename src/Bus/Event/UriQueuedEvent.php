<?php

declare(strict_types = 1);

namespace Webduck\Bus\Event;

use Symfony\Component\EventDispatcher\Event;
use Webduck\Domain\Model\Uri;

class UriQueuedEvent extends Event
{
    const NAME = 'uri_queued';

    /**
     * @var Uri
     */
    protected $uri;

    public function __construct(Uri $uri)
    {
        $this->uri = $uri;
    }

    public function getUri(): Uri
    {
        return $this->uri;
    }
}
