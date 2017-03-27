<?php

namespace Algolia\AlgoliaSearchBundle\Tests\Traits;

use Doctrine\ODM\MongoDB\DocumentManager;

trait ODMTestTrait
{
    protected static function setupDatabase()
    {
        /** @var DocumentManager $documentManager */
        $documentManager = static::staticGetObjectManager();

        $documentManager->getSchemaManager()->dropDatabases();
        $documentManager->getSchemaManager()->ensureIndexes();
    }

    public static function staticGetObjectManager()
    {
        global $kernel;

        return $kernel->getContainer()->get('doctrine_mongodb.odm.document_manager');
    }
}
