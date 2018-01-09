<?php

namespace Algolia\SearchBundle;

use AlgoliaSearch\Version;
use Algolia\SearchBundle\DependencyInjection\SearchRequirementsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as SfKernel;

class AlgoliaSearchBundle extends Bundle
{
    public function boot()
    {
        parent::boot();

        Version::addSuffixUserAgentSegment('Symfony', SfKernel::VERSION);
        Version::addSuffixUserAgentSegment('Symfony Search Bundle', '3.0.0-BETA');
    }
    
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SearchRequirementsPass());
    }
}
