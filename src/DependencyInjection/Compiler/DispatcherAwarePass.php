<?php

declare(strict_types = 1);

namespace Webduck\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Webduck\Dispatcher\DispatcherAwareInterface;

class DispatcherAwarePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('event_dispatcher')) {
            return;
        }

        foreach ($container->getDefinitions() as $definition) {
            if (!empty($definition->getClass()) && class_exists($definition->getClass()) && $interfaces = class_implements($definition->getClass())) {
                if (in_array(DispatcherAwareInterface::class, $interfaces)) {
                    $definition->addMethodCall('setDispatcher', [
                        new Reference('event_dispatcher'),
                    ]);
                }
            }
        }
    }
}
