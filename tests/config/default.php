<?php

return [
    'prefix' => 'testing_',
    'nbResults' => 12,
    'indices' => [
        'posts' => [
            'class' => 'Algolia\SearchBundle\Entity\Post',
        ],
        'comments' => [
            'class' => 'Algolia\SearchBundle\Entity\Comment',
        ],
    ]
];
