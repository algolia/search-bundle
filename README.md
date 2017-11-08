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
      class: App\Entity\Post

    - name: comments
      class: App\Entity\Comment
      normalizers:
        - App\Normalizers\CommentNormalizer
        - Symfony\Component\Serializer\Normalizer\CustomNormalizer
        - Algolia\SearchBundle\Normalizer\SearchableArrayNormalizer

```

### Credentials

You will also need to provide Algolia App ID and Admin API key. By default they are loaded from env variables `ALGOLIA_ID` and `ALGOLIA_KEY`.

If you don't use env variable, you can set them in your `parameters.yml`.

```yml
parameters:
    env(ALGOLIA_ID): K7MLRQH1JG
    env(ALGOLIA_KEY): 0d7036b75416ad0c811f30536134b313
```

## Search

In this example we'll search for posts.

```php
$em = $this->getDoctrine()->getManager();
$indexManager = $this->get('search.index_manager');

$posts = $indexManager->search('query', Post::class, $em);
```

Note that this method will return an array of entities retrieved by Doctrine object manager (data are pulled from the database).

If you want to get the raw result from Algolia, use the `rawSearch` method.


```php
$indexManager = $this->get('search.index_manager');

$posts = $indexManager->rawSearch('query', Post::class);
```

### Pagination

To get a specific page, define the `page` (and `nbResults` if you want).

```php
$em = $this->getDoctrine()->getManager();
$indexManager = $this->get('search.index_manager');

$posts = $indexManager->search('query', Post::class, $em, 2);
// Or
$posts = $indexManager->search('query', Post::class, $em, 2, 100);
```

### Count

```php
$indexManager = $this->get('search.index_manager');

$posts = $indexManager->count('query', Post::class);
```

### Advanced search

Pass anything you want in the `parameters` array. You can pass it in any search-related method.


```php
$indexManager = $this->get('search.index_manager');

$posts = $indexManager->count('query', Post::class, 0, 10, [
		'filters' => 'comment_count>10'
]);
```


## Index entities

### Automatically

The bundle will listen to `postPersist` and `preRemove` doctrine events to keep your data in sync. You have nothing to do.

### Manually

If you want to update a post manually, you can get the `IndexManager` from the container and call the `index` method manually.

```php
$em = $this->getDoctrine()->getManager();
$indexManager = $this->get('search.index_manager');
$post = $em->getRepository(Post::class)->findBy(['author' => 1]);

$indexManager->index($post, $em);

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
