
# Algolia Search API Client for Symfony

[Algolia Search](https://www.algolia.com) is a hosted full-text, numerical,
and faceted search engine capable of delivering realtime results from the first keystroke.

[![Build Status](https://travis-ci.org/algolia/search-bundle.svg?branch=master)](https://travis-ci.org/algolia/search-bundle) [![Latest Stable Version](https://poser.pugx.org/algolia/search-bundle/v/stable)](https://packagist.org/packages/algolia/search-bundle) [![License](https://poser.pugx.org/algolia/search-bundle/license)](https://packagist.org/packages/algolia/search-bundle)


This bundle provides an easy way to integrate Algolia Search into your Symfony application (using Doctrine). It allows you to index your data, keep it in sync, and search through it.



## API Documentation

You can find the full reference on [Algolia's website](https://www.algolia.com/doc/api-client/symfony/).



1. **[Getting Started](#getting-started)**
    * [Introduction](#introduction)
    * [Compatibility](#compatibility)
    * [What’s new](#what’s-new)
    * [Install](#install)
    * [Algolia credentials](#algolia-credentials)
    * [Injecting services](#injecting-services)

1. **[Configuration](#configuration)**
    * [Create <code>algolia_search.yaml</code>](#create-codealgolia_searchyamlcode)
    * [Indexing data](#indexing-data)
    * [Per environment setup](#per-environment-setup)

1. **[Indexing](#indexing)**
    * [Prerequisite](#prerequisite)
    * [Indexing manually](#indexing-manually)
    * [Removing manually](#removing-manually)
    * [Indexing automatically via Doctrine Events](#indexing-automatically-via-doctrine-events)
    * [Indexing conditionally](#indexing-conditionally)

1. **[Customizing](#customizing)**
    * [Normalizers](#normalizers)
    * [Using annotations](#using-annotations)
    * [Using <code>normalize()</code>](#using-codenormalizecode)
    * [Using a custom Normalizer](#using-a-custom-normalizer)
    * [Ordering Normalizers](#ordering-normalizers)

1. **[Search](#search)**
    * [Simple Search](#simple-search)
    * [Raw search](#raw-search)
    * [Pagination](#pagination)
    * [Count](#count)
    * [Advanced search](#advanced-search)

1. **[Managing settings](#managing-settings)**
    * [Backup and restore settings](#backup-and-restore-settings)

1. **[Advanced](#advanced)**
    * [Using Algolia Client](#using-algolia-client)
    * [Other engines](#other-engines)

1. **[Extending](#extending)**
    * [Extending Engine and SettingsManager](#extending-engine-and-settingsmanager)
    * [Create your own <em>engine</em>](#create-your-own-emengineem)
    * [Create your own <em>settings manager</em>](#create-your-own-emsettings-managerem)

1. **[Troubleshooting](#troubleshooting)**
    * [No <code>serializer</code> service found](#no-codeserializercode-service-found)
    * [The group annotation was not taken into account](#the-group-annotation-was-not-taken-into-account)





# Getting Started



## Introduction

This bundle provides an easy way to integrate Algolia Search into your Symfony application (using Doctrine). It allows you to index your data, keep it in sync, and search through it.

## Compatibility

This documentation refers to the Algolia/SearchBundle 3.0 and later. It's
compatible with Symfony 3.4 LTS and Symfony 4.0 (and later).

If your app is running Symfony prior to Symfony 3.4, please use v2. You
can find the documentation in the [README](https://github.com/algolia/search-bundle/tree/2.x).
Version 2.x is not actively maintained but will receive updates if necessary.

### Upgrade

To upgrade your project to the newest version of the bundle, please
refer to the [Upgrade Guide](https://github.com/algolia/search-bundle/blob/master/UPGRADE-3.0.md).

## What's new

v3 has introduced a number of great new features, like the use of Symfony Serializer; but the
main reason behind this new version was to improve the developer experience.

 * **Simple**: You can get started with only 5 lines of YAML
 * **Extensible**: It lets you easily replace services by implementing Interfaces
 * **Standard**: It leverages Normalizers to convert entities for indexing
 * **Dev-friendly**: It lets you disable HTTP calls easily (while running tests, for example)
 * **Future-ready**: It lets you unsubscribe from doctrine events easily to use a messaging/queue system.

**This bundle is Search Engine-agnostic**. It means that you can use it with
any other engine, not just Algolia.

## Install

### Require the dependency (with Composer)

```bash
composer require algolia/search-bundle
```

### Register the bundle

The bundle should be registered automatically, otherwise follow this step.

#### With Symfony 4.x

Add Algolia to `config/bundles.php`:

```php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    // ... Other bundles ...
    Algolia\SearchBundle\AlgoliaSearchBundle::class => ['all' => true],
];

```

#### With Symfony 3.4

Add Algolia to your `app/Kernel.php`:

```php
$bundles = array(
    new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
    // ... Other bundles ...
    new Algolia\SearchBundle\AlgoliaSearchBundle(),
);
```

## Algolia credentials

You will also need to provide the Algolia App ID and Admin API key. By default, they
are loaded from environment variables `ALGOLIA_APP_ID` and `ALGOLIA_API_KEY`.

If you use `.env` config file, you can set them there.

```yml
ALGOLIA_APP_ID=XXXXXXXXXX
ALGOLIA_API_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

If you don't use environment variables, you can set them in your `parameters.yml`.

```yml
parameters:
    env(ALGOLIA_APP_ID): XXXXXXXXXX
    env(ALGOLIA_API_KEY): xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

## Injecting services

Most of the time, you will be using the `IndexManager` object to either:

  - Check configuration
  - Index data
  - Search

### Symfony 4

Symfony 4 ships with a lighter container where only some much-needed core services
are registered. If your controller will be responsible for some search-related task,
you need to inject it via the constructor. Good news: by type-hinting the variable,
Symfony will handle everything for you thanks to auto-wiring.

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Algolia\SearchBundle\IndexManagerInterface;

class ExampleController extends Controller
{

    protected $indexManager;

    public function __construct(IndexManagerInterface $indexingManager)
    {
        $this->indexManager = $indexingManager;
    }
}
```

Notice that you type-hint an interface, not an actual implementation. This will
be very handy if you ever need to implement your own IndexManager.

### Symfony 3

Symfony 3 still uses a container holding all public services, and services are
public by default. This way, you can easily get the `search.index_manager` from
the container.

Although, it's not considered a best practice by Symfony anymore, and in order to
get ready for Symfony 4, I'd recommend using the previously shown method.

```php
// In a Container-Aware class
$indexManager = $this->getContainer()->get('search.index_manager');

// In a Controller class
$indexManager = $this->get('search.index_manager');

```




# Configuration



## Create `algolia_search.yaml`

Configuration typically lives in the `config/packages/algolia_search.yaml` file for a
Symfony 4 application.

This is how you define what entity you want to index and some other technical details
like a prefix or the number of results.

**Note:** The documentation uses the [Symfony/demo](https://github.com/symfony/demo) app as an example;
we are working with posts and comments.

#### The simplest version

```yaml
algolia_search:
  indices:
    - name: posts
      class: App\Entity\Post

    - name: comments
      class: App\Entity\Comment
```

#### A more complete example

```yaml
algolia_search:
  nbResults: 8                  # Retrieve less results on search (default: 20)
  prefix: %env(SEARCH_PREFIX)%  # Use a prefix for index names based en env var
  doctrineSubscribedEvents: []  # disable doctrine events (turn off realtime sync)
  indices:
    - name: posts
      class: App\Entity\Post
      enable_serializer_groups: true

    - name: comments
      class: App\Entity\Comment
```

## Indexing data

First, we need to define which entities should be indexed in Algolia.
Each entry under the `indices` config key must contain at least the 2 following attributes:

* `name` is the canonical name of the index in Algolia
* `class` is the full name of the entity to index

Example:

```yaml
algolia_search:
  indices:
    - name: posts
      class: App\Entity\Post
```

#### enable_serializer_groups

Before sending your data to Algolia, each entity will be converted to an array
using the Symfony built-in serializer. This option lets you define what
attribute you want to index using the annotation `@Groups({"searchable"})`.

Read more about [how entities are serialized here](https://www.algolia.com/doc/api-client/symfony/customizing/).

Example:

```yaml
algolia_search:
  indices:
    - name: posts
      class: App\Entity\Post
      enable_serializer_groups: true
```

Check out the [indexing documentation](https://www.algolia.com/doc/api-client/symfony/indexing/) to learn how to send data to Algolia.

#### Batching

By default, calls to algolia to index or remove data are batched per 500 items. You can easily
modify the batch size in your configuration.

```yaml
algolia_search:
  batchSize: 250
```

The import command also follows this parameter to retrieve data via Doctrine. If you are running out of
memory while importing your data, use a smaller `batchSize` value.

#### Using JMS Serializer

The bundle also provides basic support for the JMS Serializer. Note that not all features are supported (like the @Groups annotation).
In your config, pass the name of the JMS Serializer service (`jms_serializer` by default).

```yaml
algolia_search:
  serializer: jms_serializer
  indices:
    - name: posts
      class: App\Entity\Post
```

## Per environment setup

Usually, you need different configurations per environment, at least to avoid
touching prod data while developing.

### Bypass calls to Algolia

While working locally you might want to bypass all calls to Algolia and this
bundle has introduced new ways to do so.

1. You can [unsubscribe from Doctrine events](https://www.algolia.com/doc/api-client/symfony/indexing/#indexing-automatically-via-doctrine-events) to avoid calls on data updates.
2. You can [use the `NullEngine`](https://www.algolia.com/doc/api-client/symfony/advanced/#other-engines) to mute all calls.

### Prefix

The first thing to do is to set a prefix per environment. There are 2 ways to do that: 
either you create 2 config files or you rely on environment variables.

#### Env variables

In your config file, you set the prefix in an environment variable.

```yaml
algolia_search:
  prefix: %env(SEARCH_PREFIX)%
```

Then you define your prefix in your `.env`, or your Apache/Nginx configuration.
Symfony makes it easy to concatenate environment variables in the `.env` file.

Assuming APP_ENV is an environment variable:

```sh
SEARCH_PREFIX=app1_${APP_ENV}_
```

#### Override configuration per environment

Or you can create a config file inside the `dev` directory and override the config.

```yaml
# config/packages/algolia_search.yaml
algolia_search:
  prefix: app_prod_
```

```yaml
# config/packages/dev/algolia_search.yaml
algolia_search:
  prefix: app_dev_
```




# Indexing



## Prerequisite

Once you configured what entities you want to index in Algolia, you are ready to send data.

In the following section, we consider [this Post entity](https://gist.github.com/julienbourdeau/3d17304951028cf370ed5fe95d104911) and the following configuration.

```yaml
algolia_search:
  indices:
    - name: posts
      class: App\Entity\Post
    - name: comments
      class: App\Entity\Comment
```

## Indexing manually

### Via CLI

Once your `indices` config is ready, you can use the built-in console command
to batch import all existing data.

```sh
# Import all indices
php bin/console search:import

# Choose what indices to reindex by passing the index name
php bin/console search:import --indices=posts,comments
```

Before re-indexing everything, you may want to clear the index first,
see [how to remove data](#removing-manually).

### Programmatically

To index any entities in your code, you will need to use
[the IndexManager service](https://www.algolia.com/doc/api-client/symfony/getting-started/#injecting-services). You need to pass
it the objects to index and their ObjectManager. Objects can be a single entity, an array of entities or
even an array of different entities as long as they are using the same ObjectManager.

```php
$indexManager->index($post, $entityManager);

$indexManager->index($posts, $entityManager);

$indexManager->index($postsAndComments, $entityManager);
```

## Removing manually

### Via CLI

You may want to completely clear your indices (before reindexing for example),
you can use the `search:clear` command.

```sh
# Import all indices
php bin/console search:clear

# Choose what indices to reindex by passing the index name
php bin/console search:clear --indices=posts,comments
```

### Programmatically

The same way you [index data](#indexing-manually), you can use the `remove` method
to delete entries from the Algolia index.

```php
$indexManager->remove($post, $entityManager);

$indexManager->remove($posts, $entityManager);

$indexManager->remove($postsAndComments, $entityManager);
```

## Indexing automatically via Doctrine Events

By default, the bundle listens to the following Doctrine events:
`postPersist`, `postUpdate`, `preRemove`. Every time data are inserted, updated
or deleted via Doctrine, your Algolia index will stay in sync.

You can easily modify which events the bundle subscribes to via the `doctrineSubscribedEvents`
config key.

You can unsubscribe from all events by passing an empty array. This can become
very handy if you are working with a queue (like RabbitMQ) or if you don't
want to call Algolia in your dev environment.

```yaml
# Only insert new data (no update, no deletion)
algolia_search:
  doctrineSubscribedEvents: ['postPersist']

# Unsubscribe from all events
algolia_search:
  doctrineSubscribedEvents: []

```

## Indexing conditionally

Most of the time, there are some of your items that you don't want to index. For instance, you may want
to only index a post if it's published.

In your configuration, you can specify when a post should be indexed via
the `index_if` key. Because we rely on the [PropertyAccess component](http://symfony.com/doc/current/components/property_access.html)
you can pass a method name, a class property name or even a nested key in an property array.

The property must evaluate to true to index the entity and false to bypass indexing.
If you're updating an entity via doctrine and this property evaluates to false, the entity will be removed.

**Example with a method or a property**

```yaml
algolia_search:
  indices:
    - name: posts
      class: App\Entity\Post
      index_if: isPublished
```

In this case, `isPublished` could be a method or a class property.

With a method:

```php
class Post
{
    public function isPublished()
    {
        return !is_null($this->published_at);
    }
}
```

With a property:

```php
class Post
{
    public $isPublished = true;
}
```

**Example with an array**

```yaml
algolia_search:
  indices:
    - name: posts
      class: App\Entity\Post
      index_if: config.indexable
```

In this case, the bundle will read this value.

```php
class Post
{
    public $config = ['indexable' => false];
}
```




# Customizing



## Normalizers

By default all entities are converted to an array with the built-in [Symfony Normalizers](https://symfony.com/doc/current/components/serializer.html#normalizers) (GetSetMethodNormalizer, DateTimeNormalizer, ObjectNormalizer...) which should be enough for simple use cases, but we encourage you to write your own Normalizer to have more control over what you send to Algolia, or to avoid [circular references](https://symfony.com/doc/current/components/serializer.html#handling-circular-references).

Symfony will use the first Normalizer in the array to support your entity or format. You can [change the
order](/doc/api-client/symfony/customizing/#ordering-normalizers) in your service declaration.

**Note:** Note that the normalizer is called with _searchableArray_ format.

You have many choices on how to customize your records:

* [Use annotations in entity](https://www.algolia.com/doc/api-client/symfony/customizing/#using-annotations) (similar to how you did it with previous version of the bundle).
* [Write custom method in entity](https://www.algolia.com/doc/api-client/symfony/customizing/#using-normalize)
* [Write custom Normalizer class](https://www.algolia.com/doc/api-client/symfony/customizing/#using-a-custom-normalizer)

The following features are only supported with the [default Symfony serializer](http://symfony.com/doc/current/components/serializer.html), not with [JMS serializer](http://jmsyst.com/libs/serializer).

## Using annotations

Probably the easiest way to choose which attribute to index is to use
annotation. If you used the bundle before version 3, it's very similar. This feature relies on the built-in ObjectNormalizer and its group feature.

Example based on a simplified version of [this Post entity](https://gist.github.com/julienbourdeau/3d17304951028cf370ed5fe95d104911):

Annotations requires `enable_serializer_groups` to be true in the configuration. [Read more](https://www.algolia.com/doc/api-client/symfony/configuration/#enableserialisergroups)

    
```php
<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

class Post
{
    // ... Attributes and other methods ...

    /**
     * @Groups({"searchable"})
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @Groups({"searchable"})
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @Groups({"searchable"})
     */
    public function getCommentCount(): ?int
    {
        return count($this->comments);
    }
}
```

## Using `normalize()`

Another quick and easy way is to implement a dedicated method
that will return the entity as an array. This feature relies on the `CustomNormalizer`
that ships with the serializer component.

Implement the `Symfony\Component\Serializer\Normalizer\NormalizableInterface` interface and write your `normalize` method.

Example based on a simplified version of [this Post entity](https://gist.github.com/julienbourdeau/3d17304951028cf370ed5fe95d104911):

```php
<?php

namespace App\Entity;

use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class Post implements NormalizableInterface
{
    public function normalize(NormalizerInterface $serializer, $format = null, array $context = array()): array
    {
        return [
            'title' => $this->getTitle(),
            'content' => $this->getContent(),
            'comment_count' => $this->getComments()->count(),
            'tags' => array_unique(array_map(function ($tag) {
              return $tag->getName();
            }, $this->getTags()->toArray())),

            // Reuse the $serializer
            'author' => $serializer->normalize($this->getAuthor(), $format, $context),
            'published_at' => $serializer->normalize($this->getPublishedAt(), $format, $context),
        ];
    }
}
```

### Handle multiple formats

In case you are already using this method for something else, like encoding entities into JSON for instance, you may want to use a different format for both use cases. You can rely
on the format to return different arrays.

```php
public function normalize(NormalizerInterface $serializer, $format = null, array $context = array()): array
{
    if (\Algolia\SearchBundle\Searchable::NORMALIZATION_FORMAT === $format) {
        return [
            'title' => $this->getTitle(),
            'content' => $this->getContent(),
            'author' => $this->getAuthor()->getFullName(),
        ];
    }

    // Or if it's not for search
    return ['title' => $this->getTitle()];
}
```

## Using a custom Normalizer

You can create a custom normalizer for any entity. The following snippet shows a simple CommentNormalizer. Normalizer must implement `Symfony\Component\Serializer\Normalizer\NormalizerInterface` interface.

```php
<?php
// src/Serializer/Normalizer/UserNormalizer.php (SF4)
// or src/AppBundle/Serializer/Normalizer/UserNormalizer.php (SF3)

namespace App\Serializer\Normalizer;

use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserNormalizer implements NormalizerInterface
{
    /**
     * Normalize a user into a set of arrays/scalars.
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            'id'       => $object->getId(),
            'username' => $object->getUsername(),
        ];
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof User;

        // Or if you want to use it only for indexing
        // return $data instanceof User && Searchable::NORMALIZATION_FORMAT === $format;
    }
}
```

Then we need to tag our normalizer to add it to the default serializer. In
your service declaration, add the following.

In YAML:

```yaml
services:
    user_normalizer:
        class: App\Serializer\Normalizer\UserNormalizer
        tag: serializer.normalizer
        public: false # false by default in Symfony4
```

In XML:

```xml
<services>
    <service id="user_normalizer" class="App\Serializer\Normalizer\UserNormalizer" public="false">
        <tag name="serializer.normalizer" />
    </service>
</services>
```

The beauty is that, by following the above example, the `Author` of the `Post`
will be converted with this normalizer.

## Ordering Normalizers

Because Symfony will use the first normalizer that supports your entity or format, you
will want to pay close attention to the order.

The `ObjectNormalizer` is registered with a priority of -1000 and should always be last.
All normalizers registered by default in Symfony are between -900 and -1000 and the
`CustomNormalizer` is registered at -800.

All your normalizers should be above -800. Default priority is 0.

If this doesn't suit you, the priority can be changed in your service definition.

In YAML:

```yaml
services:
    serializer.normalizer.datetime:
        class: Symfony\Component\Serializer\Normalizer\DateTimeNormalizer
        name: serializer.normalizer
        priority: -100
```

In XML:

```xml
<services>
    <service id="serializer.normalizer.datetime" class="Symfony\Component\Serializer\Normalizer\DateTimeNormalizer">
        <!-- Run before serializer.normalizer.object -->
        <tag name="serializer.normalizer" priority="-100" />
    </service>
</services>
```




# Search



## Simple Search

In this example we'll search for posts. The `search` method will query Algolia
to get matching results and then will create a doctrine collection. The data are
pulled from the database (that's why you need to pass the Doctrine Manager).

Notice that I use `$this->indexManager` here because your IndexManager must be
injected in your class. [Read how to inject the IndexManager here](https://www.algolia.com/doc/api-client/symfony/getting-started/#injecting-services).

```php
$em = $this->getDoctrine()->getManagerForClass(Post::class);

$posts = $this->indexManager->search('query', Post::class, $em);
```

## Raw search

If you want to get the raw results from Algolia, use the `rawSearch` method. This
is the method you'll need to use if you want to retrieve the highlighted snippets
or ranking information for instance.

```php
$posts = $this->indexManager->rawSearch('query', Post::class);
```

## Pagination

To get a specific page, define the `page` (and `nbResults` if you want).

```php
$em = $this->getDoctrine()->getManagerForClass(Post::class);

$posts = $this->indexManager->search('query', Post::class, $em, 2);
// Or
$posts = $this->indexManager->search('query', Post::class, $em, 2, 100);
```

## Count

```php
$posts = $this->indexManager->count('query', Post::class);
```

## Advanced search

Search-related methods have take a `$parameters` array as the last arguments. You can pass any search parameters (in the Algolia sense).

  
```php
$em = $this->getDoctrine()->getManagerForClass(Post::class);

$posts = $this->indexManager->search('query', Post::class, $em, 1, 10, ['filters' => 'comment_count>10']);
// Or
$posts = $this->indexManager->rawSearch('query', Post::class, 1, 10, ['filters' => 'comment_count>10']);
```
  
Note that `search` will only take IDs and use doctrine to create a collection of entities so you can only pass parameters
  to modify what to search, not to modify the type of response.

If you want to modify the attributes to retrieve or retrieve data like `facets`, `facets_stats`, `_rankingInfo` you will need to use the `rawSearch` method.
  
```php
$results = $this->indexManager->rawSearch('query', Post::class, 1, 10, [
  'facets' => ['*'], // Retrieve all facets
  'getRankingInfo' => true,
]);
  
$results = $this->indexManager->rawSearch('query', Post::class, 1, 10, [
  'facets' => ['tags', 'year'],
  'attributesToRetrieve' => ['title', 'author_name'],
  'getRankingInfo' => true,
]);
```




# Managing settings



## Backup and restore settings

This bundle has a simple approach to settings management, everything is
centralized in json files. Each engine must provide a `SettingsManager` class
that can backup settings from the engine and push them back.

The bundle offers 2 commands to easily backup and restore settings

```sh
php bin/console search:settings:backup --indices:posts,comments
php bin/console search:settings:push --indices:posts,comments
```

The `--indices` option take a comma-separated list of index names (without
prefix, as defined in configuration).
If no options are passed **all indices** will be processed.

### Settings directory

Depending on your version of Symfony, the settings will be saved in different locations:

- **Symfony4**: /config/settings/algolia_search/
- **Symfony3**: /app/Resources/SearchBundle/settings/

The settings directory can also be set in the configuration if you have a
non-standard setup or if you wish to save them elsewhere. The project directory
will automatically be prepended.

```yaml
algolia_search:
    settingsDirectory: app/search-settings/
```




# Advanced



## Using Algolia Client

In some cases, you may want to access the Algolia client directly to perform advanced operations
(like manage API keys, manage indices and such).

By default, the `AlgoliaSearch\Client` is not public in the container, but you can easily expose it.
In the service file of your project, `config/services.yaml` in a typical Symfony 4 app,
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

    protected $indexManager;

    public function __construct(IndexManagerInterface $indexingManager)
    {
        $this->indexManager = $indexingManager;
    }

    public function testAction()
    {
        $algoliaClient = $this->get('algolia.client');
        var_dump($algoliaClient->listIndexes());

        $index = $algoliaClient->initIndex(
            $this->indexManager->getFullIndexName(Post::class)
        );

        var_dump($index->listApiKeys());

        die;
    }
}
```

## Other engines

Everything related to Algolia is contained in the `AlgoliaEngine` class, hence it's
easy to use the bundle with another search engine. It also allows you to write
your own engine if you want to do things differently.

### Using another Engine

Considering that you have this `AnotherEngine` class implementing the `EngineInterface`,
and you want to use it, you can override the service `search.engine` definition
in your `config/services.yaml` this way:

```yaml
services:
    search.engine:
        class: Algolia\SearchBundle\Engine\AnotherEngine
```

Or in XML

```xml
<services>
    <service id="search.engine" class="Algolia\SearchBundle\Engine\AnotherEngine" />
</services>
```

### About the `NullEngine`

The package ships with a `\Algolia\SearchBundle\Engine\NullEngine` engine class. This engine implements the `EngineInterface`
interface and returns an empty array, zero or null depending on the methods.
This is a great way to make sure everything works, without having to call Algolia.

You can use it for your tests but also in a dev environment.

### About the `AlgoliaSyncEngine`

In Algolia, all indexing operations are asynchronous. The API will return a taskID and you can check
if this task is completed or not, via another API endpoint.

For test purposes, we use the AlgoliaSyncEngine. It will always wait for task to be completed
before returning. This engine is only auto-loaded during the tests. If you use it in your
project, you can copy it into your app and modify the `search.engine` service definition.




# Extending



## Extending Engine and SettingsManager

One of the best thing about the v3 of the Algolia/SearchBundle is that you can
extend it. It is open to unlimited possibilities.

There are 2 main reasons you might need to extend this package:

- You have specific needs with Algolia
- You want to use another search engine

**Warning:** To help you get started, we recommend using [our skeleton project](https://github.com/algolia/search-bundle-skeleton).

## Create your own _engine_

### Write new `CustomEngine` class

The first mandatory step to extend the bundle is to write your own Engine. It
requires you to implement the [`EngineInterface`](https://github.com/algolia/search-bundle/blob/master/src/Engine/EngineInterface.php).

If you need inspiration, the bundle ships with
[`AlgoliaEngine`](https://github.com/algolia/search-bundle/blob/master/src/Engine/AlgoliaEngine.php)
and [`NullEngine`](https://github.com/algolia/search-bundle/blob/master/src/Engine/NullEngine.php).
In the tests, you will also find an [`AlgoliaSyncEngine`](https://github.com/algolia/search-bundle/blob/master/tests/Engine/AlgoliaSyncEngine.php).

```php
// src/Engine/CustomEngine

namespace App\Engine;

use Algolia\SearchBundle\Engine\EngineInterface;

class CustomEngine implements EngineInterface
{

    public function add($searchableEntities)
    {
        // TODO: Implement add() method.
    }

    public function update($searchableEntities)
    {
        // TODO: Implement update() method.
    }

    public function remove($searchableEntities)
    {
        // TODO: Implement remove() method.
    }

    public function clear($indexName)
    {
        // TODO: Implement clear() method.
    }

    public function delete($indexName)
    {
        // TODO: Implement delete() method.
    }

    public function search($query, $indexName, $page = 0, $nbResults = null, array $parameters = [])
    {
        // TODO: Implement search() method.
    }

    public function searchIds($query, $indexName, $page = 0, $nbResults = null, array $parameters = [])
    {
        // TODO: Implement searchIds() method.
    }

    public function count($query, $indexName)
    {
        // TODO: Implement count() method.
    }
}

```

### Override the service definition

The engine is injected in the `IndexManager` by changing the service definition
of `search.engine`. It will use your brand new class.

```yaml
search.engine:
    class: App\Engine\CustomEngine
    public: true
```

## Create your own _settings manager_

You may need to change the way settings are handled. In this case, you can
define your own `search.settings_manager`.

If you are using the console command, the `$params` argument
is a list of index plus all other arguments passed to the command.

```php
// src/Engine/CustomEngine

namespace App\Engine;

use Algolia\SearchBundle\Settings\SettingsManagerInterface;

class CustomSettingsManager implements EngineInterface
{

    public function backup(array $params)
    {
        // TODO: Implement backup() method.
    }

    public function push(array $params)
    {
        // TODO: Implement push() method.
    }
}

### Override the service definition

The engine is injected in the `IndexManager` by changing the service definition
of `search.engine`. It will use your brand new class.

```yaml
search.settings_manager:
    class: App\Engine\CustomSettingsManager
    public: true
```




# Troubleshooting



## No `serializer` service found

If you aren't using the `symfony/framework-bundle` or the `symfony/serializer` component
you may not have any service called `serializer`. The serializer component is a
requirement to this bundle but the configuration is part of the framework-bundle.

You can enable the serializer in your `app/config/services.yml` file:

```yaml
framework:
  serializer: { enabled: true }
```

It's recommended to let the framework-bundle register it rather than doing your
own configuration, unless you know what you're doing.

## The group annotation was not taken into account

Make sure the serializer annotation is enabled in your configuration. You
can enable it in your `app/config/services.yml` file:

```yaml
framework:
  serializer: { enabled: true, enable_annotations: true }
```



