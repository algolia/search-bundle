<?php

namespace Algolia\SearchBundle;

use Algolia\AlgoliaSearch\Support\UserAgent;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as SfKernel;

class AlgoliaSearchBundle extends Bundle
{
    const VERSION = '4.0.0';

    public function boot()
    {
        parent::boot();

        UserAgent::addCustomUserAgent('Symfony Search Bundle', self::VERSION);
        UserAgent::addCustomUserAgent('Symfony', SfKernel::VERSION);
    }
}
