<?php

namespace Algolia\AlgoliaSearchBundle\Tests\Traits;

use Doctrine\ORM\Tools\SchemaTool;

trait ORMTestTrait
{
    protected static function getNeededEntities()
    {
        $namespace = 'Algolia\AlgoliaSearchBundle\Tests\\';
        $base = 'Entity';
        $baseDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $base;

        $isValidEntityFile = function ($filename) {
            return
                $filename !== 'BaseTestAwareEntity.php' &&
                preg_match('#\.php$#', $filename);
        };

        $getEntityClassName = function ($filename) use ($namespace, $base) {
            return $namespace . $base . '\\' . basename($filename, '.php');
        };

        return array_map(
            $getEntityClassName,
            array_filter(scandir($baseDir), $isValidEntityFile)
        );
    }

    protected static function setupDatabase()
    {
        $em = static::staticGetObjectManager();

        $schema = array_map(function ($class) use ($em) {
            return $em->getClassMetadata($class);
        }, static::getNeededEntities());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($schema);
        $schemaTool->createSchema($schema);
    }

    public static function staticGetObjectManager()
    {
        global $kernel;

        return $kernel->getContainer()->get('doctrine.orm.entity_manager');
    }
}
