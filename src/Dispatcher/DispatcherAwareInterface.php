<?php

declare(strict_types = 1);

namespace Webduck\Dispatcher;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface DispatcherAwareInterface
{
    public function getDispatcher(): ?EventDispatcherInterface;
    public function setDispatcher(EventDispatcherInterface $dispatcher): void;
}
