<?php

declare(strict_types = 1);

namespace Webduck\Domain\Model;

use InvalidArgumentException;
use Webduck\Domain\Collection\BrowseEventCollection;

class Browse
{
    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var BrowseEventCollection
     */
    protected $events;

    /**
     * @var array
     */
    protected $trace;

    /**
     * @var Screenshot
     */
    protected $screenshot;

    /**
     * @param string|Uri $uri
     * @param BrowseEventCollection $events
     * @param array $trace
     * @param null|Screenshot $screenshot
     */
    public function __construct($uri, BrowseEventCollection $events, array $trace, ?Screenshot $screenshot = null)
    {
        $this->setUri($uri);
        $this->events = $events;
        $this->trace = $trace;
        $this->screenshot = $screenshot;
    }

    public static function createFromArray(string $uri, array $data): self
    {
        return new self(
            $uri,
            new BrowseEventCollection(
                array_map(
                    function (array $event) {
                        return new BrowseEvent($event['name'], $event['data']);
                    },
                    $data['events']
                )
            ),
            $data['trace'],
            array_key_exists('screenshot', $data) && $data['screenshot'] ? new Screenshot('image/jpeg', true, $data['screenshot']) : null
        );
    }

    public function getUri(): Uri
    {
        return $this->uri;
    }

    public function getEvents(): BrowseEventCollection
    {
        return $this->events;
    }

    public function getTrace(): array
    {
        return $this->trace;
    }

    public function getScreenshot(): ?Screenshot
    {
        return $this->screenshot;
    }

    /**
     * @param string|Uri $uri
     *
     * @return Browse
     */
    public function setUri($uri): self
    {
        if (is_string($uri)) {
            $uri = Uri::createFromString($uri);
        } elseif (!$uri instanceof Uri) {
            throw new InvalidArgumentException(sprintf(
                'Argument 0 passed to %s must be a string or instance of %s',
                __METHOD__,
                Uri::class
            ));
        }

        $this->uri = $uri;

        return $this;
    }

    public function setEvents(BrowseEventCollection $events): self
    {
        $this->events = $events;

        return $this;
    }

    public function setTrace(array $trace): self
    {
        $this->trace = $trace;

        return $this;
    }

    public function setScreenshot(Screenshot $screenshot): self
    {
        $this->screenshot = $screenshot;

        return $this;
    }
}
