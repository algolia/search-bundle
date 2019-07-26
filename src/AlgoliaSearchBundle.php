<?php

namespace Algolia\SearchBundle;

use Algolia\AlgoliaSearch\Version;
use Algolia\SearchBundle\DependencyInjection\SearchRequirementsPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as SfKernel;

class AlgoliaSearchBundle extends Bundle
{
    const VERSION = '3.4.0';

    public function boot()
    {
        parent::boot();
    }
}
