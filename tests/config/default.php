<?php

return [
    'prefix' => 'TRAVIS_sf_',
    'nbResults' => 12,
    'batchSize' => 100,
    'indices' => [
        'posts' => [
            'class' => 'Algolia\SearchBundle\Entity\Post',
            'enable_serializer_groups' => false,
            'index_if' => null,
        ],
        'comments' => [
            'class' => 'Algolia\SearchBundle\Entity\Comment',
            'enable_serializer_groups' => false,
            'index_if' => null,
        ],
    ]
];
