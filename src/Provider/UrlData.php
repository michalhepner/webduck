<?php

declare(strict_types = 1);

namespace Webduck\Provider;

class UrlData
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var EventCollection
     */
    protected $events;

    /**
     * @var array
     */
    protected $trace;

    /**
     * @var string|null
     */
    protected $screenshot;

    public function __construct(string $url, EventCollection $events, array $trace, ?string $screenshot = null)
    {
        $this->url = $url;
        $this->events = $events;
        $this->trace = $trace;
        $this->screenshot = $screenshot;
    }

    public static function createFromArray(string $url, array $data): self
    {
        return new self(
            $url,
            new EventCollection(
                array_map(
                    function (array $event) {
                        return new Event($event['name'], $event['data']);
                    },
                    $data['events']
                )
            ),
            $data['trace'],
            array_key_exists('screenshot', $data) ? $data['screenshot'] : null
        );
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getEvents(): EventCollection
    {
        return $this->events;
    }

    public function getTrace(): array
    {
        return $this->trace;
    }

    public function getScreenshot(): ?string
    {
        return $this->screenshot;
    }
}
