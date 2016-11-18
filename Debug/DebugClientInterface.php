<?php

namespace Algolia\AlgoliaSearchBundle\Debug;

interface DebugClientInterface
{
    /**
     * @return array
     */
    public function getTransactions();
}
