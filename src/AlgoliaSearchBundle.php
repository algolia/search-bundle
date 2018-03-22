<?php

namespace Algolia\SearchBundle;

use AlgoliaSearch\Version;
use Algolia\SearchBundle\DependencyInjection\SearchRequirementsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as SfKernel;

class AlgoliaSearchBundle extends Bundle
{
    const VERSION = '3.1.1';

    public function boot()
    {
        parent::boot();

        Version::addSuffixUserAgentSegment('Symfony', SfKernel::VERSION);
        Version::addSuffixUserAgentSegment('Symfony Search Bundle', self::VERSION);
    }
    
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SearchRequirementsPass());
    }
}
