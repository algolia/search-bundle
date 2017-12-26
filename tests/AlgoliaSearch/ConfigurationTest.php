<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\DependencyInjection\Configuration;

class ConfigurationTest extends BaseTest
{
    /**
     * @dataProvider dataTestConfiguration
     *
     * @param mixed $inputConfig
     * @param mixed $expectedConfig
     */
    public function testConfiguration($inputConfig, $expectedConfig)
    {
        $configuration = new Configuration();

        $node = $configuration->getConfigTreeBuilder()
            ->buildTree();
        $normalizedConfig = $node->normalize($inputConfig);
        $finalizedConfig = $node->finalize($normalizedConfig);

        $this->assertEquals($expectedConfig, $finalizedConfig);
    }

    public function dataTestConfiguration()
    {
        return [
            'test empty config for default value' => [
                [],
                [
                    "prefix" => null,
                    "nbResults" => 20,
                    "settingsDirectory" => null,
                    "doctrineSubscribedEvents" => ["postPersist", "postUpdate", "preRemove"],
                    "indices" => [],
                ]
            ],
            'Simple config' => [
                [
                    "prefix" => "sf_",
                    "nbResults" => 40,
                ],[
                    "prefix" => "sf_",
                    "nbResults" => 40,
                    "settingsDirectory" => null,
                    "doctrineSubscribedEvents" => ["postPersist", "postUpdate", "preRemove"],
                    "indices" => [],
                ]
            ],
            'Index config' => [
                [
                    "prefix" => "sf_",
                    "indices" => [
                        ['name' => 'posts', 'class' => 'App\Entity\Post'],
                        ['name' => 'tags', 'class' => 'App\Entity\Tag', 'enable_serializer_groups' => true],
                    ],
                ],[
                    "prefix" => "sf_",
                    "nbResults" => 20,
                    "settingsDirectory" => null,
                    "doctrineSubscribedEvents" => ["postPersist", "postUpdate", "preRemove"],
                    "indices" => [
                        'posts' => [
                            'class' => 'App\Entity\Post',
                            'enable_serializer_groups' => false
                        ],
                        'tags' => [
                            'class' => 'App\Entity\Tag',
                            'enable_serializer_groups' => true
                        ],
                    ],
                ]
            ],
        ];
    }
}
