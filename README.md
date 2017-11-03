# Symfony Searchable

This package will help you get your data indexed in Algolia

## Install

With Symfony Flex:

```
composer req search
```

Otherwise

```
composer require algolia/symfony-searchable
```

## Configuration

The following configuration assume you are using [Symfony/demo](http://github.com/Symfony/demo) project.

```yaml
algolia_search:
  prefix: demoapp_
  indices:
    - name: posts
      classes: [App\Entity\Post]
      normalizers:
        - Symfony\Component\Serializer\Normalizer\CustomNormalizer
        - Algolia\SearchBundle\Encoder\SearchableArrayNormalizer

    - name: comments
      classes: [App\Entity\Comment]
      normalizers: [App\Normalizers\CommentNormalizer]

    - name: all
      classes: [App\Entity\Post, App\Entity\Tag]
      normalizers:
        - App\Normalizers\CommentNormalizer
        - Symfony\Component\Serializer\Normalizer\CustomNormalizer
        - Algolia\SearchBundle\Encoder\SearchableArrayNormalizer

```


## Normalizers

By default all entities are converted to an array with the `Algolia\SearchBundle\Normalizer\SearchableArrayNormalizer`.

It converts the entity WITHOUT the relationships. And convert DateTime object to timestamps.

You can define as many normalizers as you want. Symfony will use the first one to support your entity or format.

Note that the normalizer is called with _searchableArray_ format.

### Custom Normalizers

#### Using `normalize` method in entity

To define the `normalize` method in the entity class.

1. Implement `Symfony\Component\Serializer\Normalizer\NormalizableInterface`
2. Define `normalize` method

**Example**

```php
public function normalize(NormalizerInterface $normalizer, $format = null, array $context = array()): array
{
	return [
		'title' => $this->getTitle(),
		'content' => $this->getContent(),
		'author' => $this->getAuthor()->getFullName(),
	];
}
```


#### Using a dedicated normalizer

If you prefer, you can create a custom normalizer for any entity. The following snippet shows a simple CommentNormalizer. Normalizer must implement `Symfony\Component\Serializer\Normalizer\NormalizerInterface` interface.

```php
<?php

namespace App\Normalizers;


use App\Entity\Comment;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CommentNormalizer implements NormalizerInterface
{
    /**
     * Normalizes an Comment into a set of arrays/scalars.
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            'post_id' => $object->getPost()->getId(),
            'author' => $object->getAuthor()->getFullName(),
            'content' => $object->getContent(),
        ];
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Comment;
    }
}
```
