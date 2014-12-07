<?php

use Symfony\Component\Yaml\Parser as Yaml;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\DriverManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver as MongoAnnotationDriver;

$loader = require __DIR__ . '/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
MongoAnnotationDriver::registerAnnotationClasses();

// might be useful: http://php-and-symfony.matthiasnoback.nl/2011/10/symfony2-use-a-bootstrap-file-for-your-phpunit-tests-and-run-some-console-commands/

require_once __DIR__.'/AppKernel.php';

$parameters = (new Yaml())->parse(file_get_contents(__DIR__.'/config/parameters.yml'))['parameters'];

$dbParams = [
    'driver'   => $parameters['database_driver'],
    'user'     => $parameters['database_user'],
    'password' => $parameters['database_password']
];

if (array_key_exists('database_path', $parameters)) {
    $dbParams['path'] = $parameters['database_path'];
}

if (array_key_exists('database_host', $parameters)) {
    $dbParams['host'] = $parameters['database_host'];
}

if (array_key_exists('database_port', $parameters)) {
    $dbParams['port'] = $parameters['database_port'];
}

$conn = DriverManager::getConnection($dbParams);
$sm = $conn->getSchemaManager();
try {
    $sm->createDatabase($parameters['database_name']);
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'database exists') === false) {
        $conn->close();
        throw $e;
    }
}
$conn->close();

/**
 * That's dirty and it needs to be removed at some point.
 * There is a weird bug on Travis that I can't reproduce locally
 * upon first insertion into Mongo. So we do a first insertion before the tests.
 * I'd rather understand why it is needed, but so far no idea.
 */
$m = new MongoClient();
$db = $m->{$parameters['database_name']};
$collection = $db->dummy;
$collection->insert(['dummy' => 'Strangely, this seems to help on Travis']);


global $kernel;
$kernel = new AppKernel('dev', true);
$kernel->boot();

/**
 * This is ONLY used in tests.
 * We do some work to change all index names
 * when running on travis so that they incorporate
 * the travis job id and thus can run in parallel.
 */
function metaenv($env)
{
    return $env.getenv('TRAVIS_JOB_ID');
}

/**
 * Clean up our mess.
 */
register_shutdown_function(function () use ($dbParams, $parameters) {

    echo "\n\nPost PHPUnit cleanup:\n";
    global $kernel;
    echo "Waiting for pending Algolia tasks to finish...\n";

    $kernel
    ->getContainer()
    ->get('algolia.indexer')
    ->waitForAlgoliaTasks();

    $conn = DriverManager::getConnection($dbParams);
    $sm = $conn->getSchemaManager();
    echo "Dropping database {$parameters['database_name']}...\n";
    $sm->dropDatabase($parameters['database_name']);
    $conn->close();

    if (isset($dbParams['path']) && file_exists($dbParams['path'])) {
        unlink($dbParams['path']);
    }
});
