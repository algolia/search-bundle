<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests;

use Doctrine\ORM\Tools\Setup as DoctrineSetup;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\DriverManager;

// Use lightweight test doubles that can inspect internal state
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\EventListener\AlgoliaSearchDoctrineEventSubscriber;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Indexer\Indexer;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected static $em = null;
    protected static $indexer = null;

    /**
     * When set to true, all tests will be ran locally,
     * i.e. skipping the part where the actual communication with Algolia
     * is done.
     * This is used to test the Doctrine/Symfony side of things in isolation.
     */
    protected static $isolateFromAlgolia = true;

    protected static function getDbParams()
    {
        $params = require __DIR__.DIRECTORY_SEPARATOR.'secrets.php';
        return $params['db'];
    }

    protected static function getApiSettings()
    {
        $params = require __DIR__.DIRECTORY_SEPARATOR.'secrets.php';
        return $params['api'];
    }

    protected static function getNeededEntities()
    {
        $entities = array();
        $namespace = 'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\\';
        $base = 'Entity';
        foreach (scandir(__DIR__.DIRECTORY_SEPARATOR.$base) as $entry) {
            if ($entry === 'BaseTestAwareEntity.php') {
                continue;
            }

            if (preg_match('/\.php$/', $entry)) {
                $entities[] = $namespace.$base.'\\'.basename($entry, '.php');
            }
        }
        return $entities;
    }

    protected static function setupDatabase($noAlgolia = false)
    {
        $tmpParams = static::getDbParams();
        $dbname = $tmpParams['dbname'];
        unset($tmpParams['dbname']);

        $tmpConnection = DriverManager::getConnection($tmpParams);
        $sm = $tmpConnection->getSchemaManager();

        if (in_array($dbname, $sm->listDatabases())) {
            $sm->dropDatabase($dbname);
        }

        $sm->createDatabase($dbname);

        $tmpConnection->close();

        $paths = array(__DIR__.DIRECTORY_SEPARATOR.'Entity');

        $dbParams = static::getDbParams();

        $config = DoctrineSetup::createConfiguration($isDevMode = true);
        $driver = new AnnotationDriver(new AnnotationReader(), $paths);
        $config->setMetadataDriverImpl($driver);

        $evm = new EventManager();
        
        $indexer = new Indexer();

        // Setting the 2 values below is automagically handled
        // by symfony in the real world.
        $indexer->setEnvironment('dev');
        $indexer->setApiSettings(static::getApiSettings());
        
        $indexer->isolateFromAlgolia(static::$isolateFromAlgolia);

        if (!$noAlgolia) {
            $evm->addEventSubscriber(new AlgoliaSearchDoctrineEventSubscriber($indexer));
        }

        $em     = EntityManager::create($dbParams, $config, $evm);

        $schema = array_map(function ($class) use ($em) {
            return $em->getClassMetadata($class);
        }, static::getNeededEntities());

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($schema);

        static::$em = $em;
        static::$indexer = $indexer;
    }

    public static function setupBeforeClass($noAlgolia = false)
    {
        static::setupDatabase($noAlgolia);
    }

    public static function tearDownAfterClass()
    {
        static::$em->close();
    }

    public function getEntityManager()
    {
        return static::$em;
    }

    public function getIndexer()
    {
        return static::$indexer;
    }

    public function persistAndFlush($entity)
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
        return $this;
    }

    public function removeAndFlush($entity)
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
        return $this;
    }

    public function setUp()
    {
        $this->getIndexer()->reset();
    }

    public function getObjectID(array $primaryKeyData)
    {
        return $this->getIndexer()->serializePrimaryKey($primaryKeyData);
    }
}
