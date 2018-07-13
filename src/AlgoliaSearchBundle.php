<?php

namespace Algolia\SearchBundle;

use Algolia\AlgoliaSearch\Support\Config;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as SfKernel;

class AlgoliaSearchBundle extends Bundle
{
    const VERSION = '3.2.0';

    public function boot()
    {
        parent::boot();

        if (class_exists('Algolia\AlgoliaSearch\Support\Config')) {
            Config::addCustomUserAgent('Symfony Search Bundle', self::VERSION);
            Config::addCustomUserAgent('Symfony', SfKernel::VERSION);
        }
    }
}
