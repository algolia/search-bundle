<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\Engine\AlgoliaEngine;

class AlgoliaIndexManager extends IndexManager
{
    public function __construct($normalizer, AlgoliaEngine $engine, array $configuration)
    {
        parent::__construct($normalizer, $engine, $configuration);
    }

    public function setCredentials($appId, $apiKey)
    {
        $this->engine->setCredentials($appId, $apiKey);

        return $this;
    }
}
