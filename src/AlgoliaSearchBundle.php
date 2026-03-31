<?php

namespace Algolia\SearchBundle;

use Algolia\AlgoliaSearch\Support\AlgoliaAgent;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as SfKernel;

final class AlgoliaSearchBundle extends Bundle
{
    /**
     * Holds the bundle version.
     */
    public const VERSION = '8.0.0';

    public function boot(): void
    {
        parent::boot();

        AlgoliaAgent::addAlgoliaAgent('Search', 'Symfony Search Bundle', self::VERSION);
        AlgoliaAgent::addAlgoliaAgent('Search', 'Symfony', SfKernel::VERSION);
    }
}
