<?php

namespace Algolia\AlgoliaSearchBundle\Tests;

use Doctrine\ORM\Tools\SchemaTool;

// Use lightweight test doubles that can inspect internal state
use Algolia\AlgoliaSearchBundle\Tests\Indexer\Indexer;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected $backupGlobalsBlacklist = ['kernel'];

    protected static $indexer = null;

    /**
     * When set to true, all tests will be ran locally,
     * i.e. skipping the part where the actual communication with Algolia
     * is done.
     * This is used to test the Doctrine/Symfony side of things in isolation.
     */
    protected static $isolateFromAlgolia = true;

    protected static function setupDatabase()
    {
    }

    public static function setupBeforeClass()
    {
        static::setupDatabase();
    }

    public static function tearDownAfterClass()
    {
    }

    public function getObjectManager()
    {
        return static::staticGetObjectManager();
    }

    public static function staticGetObjectManager()
    {
    }

    /**
     * @return \Algolia\AlgoliaSearchBundle\Indexer\Indexer
     */
    public function getIndexer()
    {
        return static::staticGetIndexer();
    }

    public static function staticGetIndexer()
    {
        global $kernel;

        return $kernel->getContainer()->get('algolia.indexer');
    }

    public function persistAndFlush($entity)
    {
        $this->getObjectManager()->persist($entity);
        $this->getObjectManager()->flush();

        return $this;
    }

    public function removeAndFlush($entity)
    {
        $this->getObjectManager()->remove($entity);
        $this->getObjectManager()->flush();

        return $this;
    }

    public function setUp()
    {
        $this->getIndexer()->reset();
        $this->getIndexer()->isolateFromAlgolia(static::$isolateFromAlgolia);
    }

    public function getObjectID(array $primaryKeyData)
    {
        return $this->getIndexer()->serializePrimaryKey($primaryKeyData);
    }
}
