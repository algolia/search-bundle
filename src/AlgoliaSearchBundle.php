<?php

namespace Algolia\SearchBundle;

use AlgoliaSearch\Version;
use Algolia\SearchBundle\DependencyInjection\SearchRequirementsPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as SfKernel;

class AlgoliaSearchBundle extends Bundle
{
    const VERSION = '3.4.0';

    public function boot()
    {
        parent::boot();

        Version::addSuffixUserAgentSegment('Symfony Search Bundle', self::VERSION);
        Version::addSuffixUserAgentSegment('Symfony', SfKernel::VERSION);
    }
}
