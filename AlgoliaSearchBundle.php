<?php

namespace Algolia\SearchBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AlgoliaSearchBundle extends Bundle
{
    public function getAlias()
    {
        return 'search';
    }
}
