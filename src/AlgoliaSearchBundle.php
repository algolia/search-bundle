<?php

namespace Algolia\SearchBundle;

use Algolia\AlgoliaSearch\Support\UserAgent;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as SfKernel;

final class AlgoliaSearchBundle extends Bundle
{
    /**
     * Holds the bundle version.
     */
    const VERSION = '5.2.1';

    /**
     * @return void
     */
    public function boot()
    {
        parent::boot();

        UserAgent::addCustomUserAgent('Symfony Search Bundle', self::VERSION);
        UserAgent::addCustomUserAgent('Symfony', SfKernel::VERSION);
    }
}
