# Symfony Search Bundle

This package will help you get your data indexed in a dedicated Search Engine

---
### New package

You're looking at the new major version of this package. If your looking for the previous one, it was moved to the [`2.x` branch](https://github.com/algolia/AlgoliaSearchBundle/tree/2.x).

---

**Table of Contents**

- [Compatibility](#compatibility)
- [Install](#install)
- [Configuration](#configuration)
  - [Credentials](#credentials)
- [Search](#search)
  - [Pagination](#pagination)
  - [Count](#count)
  - [Advanced search](#advanced-search)
- [Index entities](#index-entities)
  - [Automatically](#automatically)
  - [Manually](#manually)
- [Normalizers](#normalizers)
  - [Custom Normalizers](#custom-normalizers)
    - [Using `normalize` method in entity](#using-normalize-method-in-entity)
    - [Using a dedicated normalizer](#using-a-dedicated-normalizer)
- [Engine](#engine)
  - [The `NullEngine`](#the-nullengine)
  - [Using another engine](#using-another-engine)
- [Using the Algolia Client (Advanced)](#using-the-algolia-client-advanced)
  - [Example](#example)
- [Tests](#tests)
  - [About `AlgoliaSyncEngine`](#about-algoliasyncengine)

## Compatibility

This package is compatible with Symfony 3.4 and higher.

If your app runs an older version, you can use the previous version, [available on the 2.x branch](https://github.com/algolia/AlgoliaSearchBundle/tree/1.x).

## Install

With Symfony Flex:

```
composer req search
```

Otherwise

```
composer require algolia/search-bundle
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

You will also need to provide Algolia App ID and Admin API key. By default they are loaded from env variables `ALGOLIA_APP_ID` and `ALGOLIA_API_KEY`.

If you don't use env variable, you can set them in your `parameters.yml`.

```yml
parameters:
    env(ALGOLIA_APP_ID): K7MLRQH1JG
    env(ALGOLIA_API_KEY): 0d7036b75416ad0c811f30536134b313
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

## Engine

### The `NullEngine`

The package ships with a NullEngine. This engine implements the `EngineInterface` and return an empty array, zero or null depending on the method.

You can use it for your test for instance but also in dev environment.

### Using another engine

Let's say you want to use the `NullEngine` in your dev environment. You can override the service
definition in your `config/dev/serices.yaml` this way:

```yaml
services:
    search.engine:
        class: Algolia\SearchBundle\Engine\NullEngine
```

Or in XML

```xml
<services>
    <service id="search.engine" class="Algolia\SearchBundle\Engine\NullEngine" />
</services>
```

This is also how you can use a custom engine, to handle another search engine or extend Algolia' default engine.

## Using the Algolia Client (Advanced)

In some cases, you may want to access the Algolia client directly to perform advanced operations
(like manage API keys, manage indices and such).

By default the `AlgoliaSearch\Client` in not public in the container, but you can easily expose it.
In the service file of your project, `config/serices.yaml` in a typical Symfony4 app,
you can alias it and make it public with the following code:

```yaml
services:
    algolia.client:
        alias: algolia_client
        public: true
```

Or in XML

```xml
<services>
    <service id="algolia.client" alias="algolia_client" public="true" />
</services>
```

### Example

Here is an example of how to use the client after your registered it publicly.

```php
class TestController extends Controller
{
    public function testAction()
    {
        $algoliaClient = $this->get('algolia.client');
        var_dump($algoliaClient->listIndexes());

        $indexManager = $this->get('search.index_manager');
        $index = $algoliaClient->initIndex(
            $indexManager->getFullIndexName(Post::class)
        );

        var_dump($index->listApiKeys());

        die;
    }
}
```

## Tests

The tests require `ALGOLIA_APP_ID` and `ALGOLIA_API_KEY` to be defined in the environment variables.

```
ALGOLIA_APP_ID=XXXXXXXXXX ALGOLIA_API_KEY=4b31300d70d70b75811f413366ad0c ./vendor/bin/phpunit
```

### About `AlgoliaSyncEngine`

In Algolia, all indexing operations are asynchronous. The API will return a taskID and you can check
if this task is completed or not via another API endpoint.

For test purposes, we use the AlgoliaSyncEngine. It will always wait for task to be completed
before returning. This engine is only autoloaded during in the tests, if you will to use it in your
project, you can copy it into your app and modify the `search.engine` service definition.
