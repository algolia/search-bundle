<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\TestApp\Entity\Comment;
use Algolia\SearchBundle\Util\ClassInfo;

class EntityProxyTest extends BaseTest
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
        ];

        foreach (['id' => 'ALGOLIA_APP_ID', 'key' => 'ALGOLIA_API_KEY'] as $key => $item) {
            if (isset($_ENV[$item])) {
                self::$values['_env_' . $key] = $_ENV[$item];
            }
            if (isset($_SERVER[$item])) {
                self::$values['_server_' . $key] = $_SERVER[$item];
            }
        }

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
        foreach (['id' => 'ALGOLIA_APP_ID', 'key' => 'ALGOLIA_API_KEY'] as $key => $item) {
            if (isset(self::$values['_env_' . $key])) {
                $_ENV[$item] = self::$values['_env_' . $key];
            }
            if (isset(self::$values['_server_' . $key])) {
                $_SERVER[$item] = self::$values['_server_' . $key];
            }
        }
    }

    public function testEntityIsNotProxied(): void
    {
        $comment = new Comment();
        self::assertEquals('Algolia\\SearchBundle\\TestApp\\Entity\\Comment', ClassInfo::getClass($comment));
    }

    public function testEntityIsProxiedWithOPM(): void
    {
        $factory = new \ProxyManager\Factory\NullObjectFactory();
        $proxy   = $factory->createProxy(Comment::class);

        self::assertStringStartsWith('ProxyManagerGeneratedProxy\\__PM__\\Algolia\\SearchBundle\\TestApp\\Entity\\Comment', get_class($proxy));
        self::assertEquals('Algolia\\SearchBundle\\TestApp\\Entity\\Comment', ClassInfo::getClass($proxy));
    }

    public function testEntityIsProxiedWithDP(): void
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
        $entityManager = $this->get('doctrine')->getManager();

        $metadata = $entityManager->getClassMetadata(Comment::class);
        $entityManager->getProxyFactory()->generateProxyClasses([$metadata]);

        $proxy = $entityManager->getProxyFactory()->getProxy($metadata->getName(), ['id' => 1]);

        self::assertStringStartsWith('Proxies\\__CG__\Algolia\\SearchBundle\\TestApp\\Entity\\Comment', get_class($proxy));
        self::assertEquals('Algolia\\SearchBundle\\TestApp\\Entity\\Comment', ClassInfo::getClass($proxy));
    }
}
