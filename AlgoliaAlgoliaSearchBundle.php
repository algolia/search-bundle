<?php

namespace Algolia\AlgoliaSearchBundle;

use Algolia\AlgoliaSearchBundle\DependencyInjection\AlgoliaAlgoliaSearchExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AlgoliaAlgoliaSearchBundle extends Bundle
{
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new AlgoliaAlgoliaSearchExtension();
        }

        return $this->extension;
    }
}
