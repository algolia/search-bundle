<?php

namespace Algolia\SearchBundle;

use AlgoliaSearch\Version;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as SfKernel;

class AlgoliaSearchBundle extends Bundle
{
    public function boot()
    {
        parent::boot();

        Version::addSuffixUserAgentSegment('Symfony', SfKernel::VERSION);
        Version::addSuffixUserAgentSegment('Symfony Search Bundle', '3.0.1');
    }
}
