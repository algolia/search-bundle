<?php

return [
    'prefix' => 'TRAVIS_sf_',
    'nbResults' => 12,
    'indices' => [
        'posts' => [
            'class' => 'Algolia\SearchBundle\Entity\Post',
            'enable_serializer_groups' => false,
            'object_id' => 'getSlug',
        ],
        'comments' => [
            'class' => 'Algolia\SearchBundle\Entity\Comment',
            'enable_serializer_groups' => false,
            'object_id' => null,
        ],
    ]
];
