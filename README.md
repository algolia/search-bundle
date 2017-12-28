# Symfony Search Bundle

This package will help you get your data indexed in a dedicated Search Engine

---
### New package

You're looking at the new major version of this package. If your looking for the previous one, it was moved to the [`2.x` branch](https://github.com/algolia/search-bundle/tree/2.x).

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
    - [Using a dedicated normalizer](#using-a-dedicated-normalizer)
    - [Using `normalize` method in entity](#using-normalize-method-in-entity)
    - [Create Normalizer for `Algolia\SearchBundle` only](#create-normalizer-for-algoliasearchbundle-only)
  - [Using `@Groups` annotation](#using-normalizer-groups)
- [Engine](#engine)
  - [The `NullEngine`](#the-nullengine)
  - [Using another engine](#using-another-engine)
- [Using the Algolia Client (Advanced)](#using-the-algolia-client-advanced)
  - [Example](#example)
- [Managing settings](#managing-settings)
- [Tests](#tests)
  - [About `AlgoliaSyncEngine`](#about-algoliasyncengine)

## Compatibility

This package is compatible with Symfony 3.4 and higher.

If your app runs an older version, you can use the previous version, [available on the 2.x branch](https://github.com/algolia/search-bundle/tree/2.x).

## Install

With composer

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
      enable_serializer_groups: true
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
$em           = $this->getDoctrine()->getManager();
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
$em           = $this->getDoctrine()->getManager();
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

$posts = $indexManager->count('query', Post::class, 0, 10, ['filters' => 'comment_count>10']);
```


## Index entities

### Automatically

The bundle will listen to `postPersist` and `preRemove` doctrine events to keep your data in sync. You have nothing to do.

### Manually

If you want to update a post manually, you can get the `IndexManager` from the container and call the `index` method manually.

```php
$em           = $this->getDoctrine()->getManager();
$indexManager = $this->get('search.index_manager');
$post         = $em->getRepository(Post::class)->findBy(['author' => 1]);

$indexManager->index($post, $em);

```

### With commands

The bundle ships with two commands to import data into an index or to clear it.

```
# Import all data
php bin/console search:import

# Select what data to import
php bin/console search:import --indices=posts,comments
```

It works the same way to clear data


```
# Clear all indices
php bin/console search:clear

# Select what indices to clear
php bin/console search:clear --indices=posts,comments
```

## Normalizers

By default all entities are converted to an array with the built-in [Symfony Normalizers](https://symfony.com/doc/current/components/serializer.html#normalizers) (GetSetMethodNormalizer, DateTimeNormalizer, ObjectNormalizer...) which should be enough for simple use case, but we encourage you to write your own Normalizer to have more control on what you send to Algolia or to simply avoid [circular references](https://symfony.com/doc/current/components/serializer.html#handling-circular-references).

Symfony will use the first one to support your entity or format.

Note that the normalizer is called with _searchableArray_ format.

### Custom Normalizers

#### Using a dedicated normalizer

You can create a custom normalizer for any entity. The following snippet shows a simple CommentNormalizer. Normalizer must implement `Symfony\Component\Serializer\Normalizer\NormalizerInterface` interface.

```php
<?php

namespace App\Serializer\Normalizers;

use App\Entity\Comment;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CommentNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;
    
    /**
     * Normalizes an Comment into a set of arrays/scalars.
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            'post_id'   => $object->getPost()->getId(),
            'content'   => $object->getContent(),
            'createdAt' => $this->serializer->normalize($object->getCreatedAt(), $format, $context),
            'author'    => $this->serializer->normalize($object->getAuthor(), $format, $context),
        ];
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Comment;
    }
}
```

```php
<?php

namespace App\Serializer\Normalizers;

use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserNormalizer implements NormalizerInterface
{
    /**
     * Normalizes an Comment into a set of arrays/scalars.
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            'username' => $object->getUsername(),
            'id'       => $object->getAuthor()->getFullName(),
        ];
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof User;
    }
}
```

Don't forget to create the new services for your newly created `Normalizers` (if you don't rely on autowiring). You can find an example on the 
(Symfony documentation)[http://symfony.com/doc/current/serializer.html#adding-normalizers-and-encoders].

In our case, it will be:

```xml
<service id="comment_normalizer" class="App\Serializer\Normalizer\CommentNormalizer" public="false">
    <tag name="serializer.normalizer" />
</service>
<service id="comment_normalizer" class="App\Serializer\Normalizer\UserNormalizer" public="false">
    <tag name="serializer.normalizer" />
</service>
```


#### Using `normalize` method in entity

To define the `normalize` method in the entity class.

1. Implement `Symfony\Component\Serializer\Normalizer\NormalizableInterface`
2. Define `normalize` method

**Example**

```php
<?php

public function normalize(NormalizerInterface $normalizer, $format = null, array $context = array()): array
{
	return [
		'title'   => $this->getTitle(),
		'content' => $this->getContent(),
		'author'  => $this->getAuthor()->getFullName(),
	];
}
```

#### Create Normalizer for `Algolia\SearchBundle` only

Sometimes, you want to create a specific `Normalizer` just to send data to Algolia. But what happens if you have 2 normalizers for the same class ?
Well you can add a condition so your `Normalizer` will only be called when Indexing through Algolia.

When you create your `Normalizers`, you can add an additionnal check for the format :
```php
<?php

use Algolia\SearchBundle\Searchable; 

class UserNormalizer implements NormalizerInterface
{
    ... 

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof User && $format == Searchable::NORMALIZATION_FORMAT;
    }
}
```

Though it's not really encouraged to add this kind of logic inside the normalize method, you could also add/remove some specific fields on your entity for Algolia this way:

```php
<?php 

use Algolia\SearchBundle\Searchable; 

class UserNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = array())
    {
        if ($format == Searchable::NORMALIZATION_FORMAT) {
            // 'id' of user will be provided only in Algolia usage
            return ['id' => $object->getId()];
        }
        
        return [
            'username' => $object->getUsername(),
            'email'    => $object->getEmail(),
        ];
    }
    
    ...
}
```

### Using normalizer groups

You can also rely on (`@Group` annotation)[https://symfony.com/doc/current/components/serializer.html].
The name of the group is `searchable`.

You have to explicitly enable this feature on your configuration: 

```yaml
algolia_search:
  prefix: demoapp_
  indices:
    - name: posts
      class: App\Entity\Post

    - name: comments
      class: App\Entity\Comment
      enable_serializer_groups: true
```

In the example below, `$comment`, `$author` and `$createdAt` data will be sent to Algolia.

```php
<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

class Comment
{
    public $createdAt;
    public $reviewer;

    /**
     * @Groups({"searchable"})
     */
    public $comment;
    
    /**
     * @Groups({"searchable"})
     */
    public $author;
    
    /**
     * @Groups({"searchable"})
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
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

## Managing settings

This bundle has a simple approach to settings management, everything is centralized in json files. Each
engine must provide a `SettingsManager` class that can backup settings from the engine and push them back.

The bundle offers 2 commands to easily backup and restore settings

```sh
php bin/console search:settings:backup --indices:posts,comments
php bin/console search:settings:push --indices:posts,comments
```

The `--indices` option take a comma-separated list of index names (without prefix, as defined in configuration).
If no options is passed **all indices** will be processed.

### Settings directory

Depending on your version of Symfony, the settings will be saved in different locations:

- **Symfony4**: /config/settings/algolia_search/
- **Symfony3**: /app/Resources/SearchBundle/settings/

The settings directory can also be set in the configuration if you have a non-standard setup or if you
wish to save them elsewhere. The project directory will automatically be prepended.

```yaml
algolia_search:
    settingsDirectory: app/search-settings/
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
