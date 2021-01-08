<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\TestApp\Entity\Comment;
use Algolia\SearchBundle\Util\ClassInfo;

class EntityProxyTest extends BaseTest
{
    private static $values = [];

    public static function setUpBeforeClass()
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

    public static function tearDownAfterClass()
    {
        putenv('ALGOLIA_APP_ID=' . self::$values['env_id']);
        putenv('ALGOLIA_API_KEY=' . self::$values['env_key']);
        $_ENV    = self::$values['_env'];
        $_SERVER = self::$values['_server'];
    }

    public function testEntityIsNotProxied()
    {
        $comment = new Comment();
        $this->assertEquals('Algolia\\SearchBundle\\TestApp\\Entity\\Comment', ClassInfo::getClass($comment));
    }

    public function testEntityIsProxied()
    {
        $factory = new \ProxyManager\Factory\NullObjectFactory();
        $proxy   = $factory->createProxy(\Algolia\SearchBundle\TestApp\Entity\Comment::class);

        $this->assertStringStartsWith('ProxyManagerGeneratedProxy\\__PM__\\Algolia\\SearchBundle\\TestApp\\Entity\\Comment', get_class($proxy));
        $this->assertEquals('Algolia\\SearchBundle\\TestApp\\Entity\\Comment', ClassInfo::getClass($proxy));
    }
}
