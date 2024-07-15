<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;
use Symfony\Component\VarExporter\LazyObjectInterface;

class ClientProxyTest extends BaseTest
{
    private static $values = [];

    public static function setUpBeforeClass(): void
    {
        // Unset env variables to make sure Algolia
        // Credentials are only required when the
        // client is used. Save them to restore them after.
        // See: https://github.com/algolia/search-bundle/issues/241
        self::$values = [
            'env_id'  => getenv('ALGOLIA_APP_ID'),
            'env_key' => getenv('ALGOLIA_API_KEY'),
            '_env'    => $_ENV,
            '_server' => $_SERVER,
        ];

        putenv('ALGOLIA_APP_ID');
        putenv('ALGOLIA_API_KEY');
        unset($_ENV['ALGOLIA_APP_ID']);
        unset($_ENV['ALGOLIA_API_KEY']);
        unset($_SERVER['ALGOLIA_APP_ID']);
        unset($_SERVER['ALGOLIA_API_KEY']);
    }

    public static function tearDownAfterClass(): void
    {
        putenv('ALGOLIA_APP_ID=' . self::$values['env_id']);
        putenv('ALGOLIA_API_KEY=' . self::$values['env_key']);
        $_ENV    = self::$values['_env'];
        $_SERVER = self::$values['_server'];
    }

    public function testClientIsProxied(): void
    {
        $interfaces = class_implements($this->get('search.client'));

        self::assertContains(LazyObjectInterface::class, $interfaces);
    }

    public function testProxiedClientFailIfNoEnvVarsFound(): void
    {
        $this->expectException(EnvNotFoundException::class);
        $this->get('search.client')->listIndices();
    }
}
