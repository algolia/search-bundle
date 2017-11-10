<?php

return [
    'prefix' => 'testing_',
    'nbResults' => 12,
    'indices' => [
        'posts' => [
            'class' => 'Algolia\SearchableBundle\Entity\Post',
        ],
        'comments' => [
            'class' => 'Algolia\SearchableBundle\Entity\Comment',
        ],
    ]
];
