<?php

namespace Algolia\SearchBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

class SearchRequirementsPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container)
    {
        $servicesNeeded = ['serializer'];

        foreach ($servicesNeeded as $service) {
            if (!$container->has($service)) {
                throw new LogicException(sprintf('No "%s" service found. Read
                more: http://www.algolia.com/doc/api-client/symfony/troubleshooting/', $service));
            }
        }
    }
}
