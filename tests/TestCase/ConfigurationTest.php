<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\DependencyInjection\Configuration;

class ConfigurationTest extends BaseTest
{
    /**
     * @dataProvider dataTestConfigurationTree
     *
     * @param mixed $inputConfig
     * @param mixed $expectedConfig
     */
    public function testConfigurationTree($inputConfig, $expectedConfig)
    {
        $configuration = new Configuration();

        $node = $configuration->getConfigTreeBuilder()
            ->buildTree();
        $normalizedConfig = $node->normalize($inputConfig);
        $finalizedConfig = $node->finalize($normalizedConfig);

        $this->assertEquals($expectedConfig, $finalizedConfig);
    }

    public function dataTestConfigurationTree()
    {
        return [
            'test empty config for default value' => [
                [],
                [
                    "prefix" => null,
                    "nbResults" => 20,
                    "batchSize" => 500,
                    'serializer' => 'serializer',
                    "settingsDirectory" => null,
                    "doctrineSubscribedEvents" => ["postPersist", "postUpdate", "preRemove"],
                    "indices" => [],
                ]
            ],
            'Simple config' => [
                [
                    "prefix" => "sf_",
                    "nbResults" => 40,
                    "batchSize" => 100,
                ],[
                    "prefix" => "sf_",
                    "nbResults" => 40,
                    "batchSize" => 100,
                    'serializer' => 'serializer',
                    "settingsDirectory" => null,
                    "doctrineSubscribedEvents" => ["postPersist", "postUpdate", "preRemove"],
                    "indices" => [],
                ]
            ],
            'Index config' => [
                [
                    "prefix" => "sf_",
                    "indices" => [
                        ['name' => 'posts', 'class' => 'App\Entity\Post', 'index_if' => null],
                        ['name' => 'tags', 'class' => 'App\Entity\Tag', 'enable_serializer_groups' => true, 'index_if' => null],
                    ],
                ],[
                    "prefix" => "sf_",
                    "nbResults" => 20,
                    "batchSize" => 500,
                    'serializer' => 'serializer',
                    "settingsDirectory" => null,
                    "doctrineSubscribedEvents" => ["postPersist", "postUpdate", "preRemove"],
                    "indices" => [
                        'posts' => [
                            'class' => 'App\Entity\Post',
                            'enable_serializer_groups' => false,
                            'index_if' => null,
                        ],
                        'tags' => [
                            'class' => 'App\Entity\Tag',
                            'enable_serializer_groups' => true,
                            'index_if' => null,
                        ],
                    ],
                ]
            ],
        ];
    }
}
