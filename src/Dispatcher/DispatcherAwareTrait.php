<?php

declare(strict_types = 1);

namespace Webduck\Dispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

trait DispatcherAwareTrait
{
    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    public function getDispatcher(): ?EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function dispatch(string $eventName, Event $event): Event
    {
        if ($this->dispatcher) {
            return $this->dispatcher->dispatch($eventName, $event);
        }

        return $event;
    }
}
