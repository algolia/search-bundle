<?php

namespace Algolia\SearchBundle\Normalizer;

use Algolia\SearchBundle\TestApp\Entity\Comment;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CommentNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            'content' => $object->getContent(),
            'post_title' => $object->getPost()->getTitle(),
        ];
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Comment;
    }
}
