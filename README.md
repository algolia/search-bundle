# Algolia Search API Client for Symfony

[Algolia Search](https://www.algolia.com) is a hosted full-text, numerical, and faceted search engine capable of delivering realtime results from the first keystroke.

[![Build Status](https://travis-ci.org/algolia/AlgoliaSearchBundle.svg?branch=master)](https://travis-ci.org/algolia/AlgoliaSearchBundle) [![Latest Stable Version](https://poser.pugx.org/algolia/algolia-search-bundle/v/stable)](https://packagist.org/packages/algolia/algolia-search-bundle) [![License](https://poser.pugx.org/algolia/algolia-search-bundle/license)](https://packagist.org/packages/algolia/algolia-search-bundle)


This Symfony bundle provides an easy way to integrate Algolia Search into your Symfony2 with Doctrine2 application.




## API Documentation

You can find the full reference on [Algolia's website](https://www.algolia.com/doc/api-client/symfony/).


## Table of Contents


1. **[Setup](#setup)**

    * [Install](#install)
    * [Register the bundle](#register-the-bundle)
    * [Fill in your Algolia credentials](#fill-in-your-algolia-credentials)

1. **[Mapping entities to Algolia indexes](#mapping-entities-to-algolia-indexes)**

    * [Indexing entity properties or methods](#indexing-entity-properties-or-methods)
    * [Autoindexing vs Manual Indexing](#autoindexing-vs-manual-indexing)
    * [Per environment indexing](#per-environment-indexing)
    * [Conditional indexing](#conditional-indexing)
    * [Index settings](#index-settings)

1. **[Retrieving entities](#retrieving-entities)**

    * [Performing a raw search](#performing-a-raw-search)
    * [Performing a native search](#performing-a-native-search)

1. **[Reindexing](#reindexing)**

    * [Reindexing whole collections](#reindexing-whole-collections)

1. **[Running the tests](#running-the-tests)**

    * [Running the tests](#running-the-tests)




# Setup



## Install

#### With composer

```bash
composer require algolia/algolia-search-bundle
```

## Register the bundle

Add `Algolia\AlgoliaSearchBundle\AlgoliaAlgoliaSearchBundle()` to your application Kernel:
```php
$bundles = array(
    new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
    new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
    new Algolia\AlgoliaSearchBundle\AlgoliaAlgoliaSearchBundle(),
);
```

## Fill in your Algolia credentials

Add your Algolia application ID and API key to your `parameters.yml` file:

```yaml
parameters:
    database_driver: pdo_mysql
    database_host: 127.0.0.1
    # ...
    algolia.application_id: YOUR_APP_ID
    algolia.api_key: YOUR_API_KEY
```

You can also define those credentials directly in your `config.yml` file:
```yaml
algolia:
    application_id: YOUR_APP_ID
    api_key: YOUR_API_KEY
```

There's two optional parameters you can add to this file:
```yaml
algolia:
    catch_log_exceptions: true
    index_name_prefix: staging
    connection_timeout: 2
```
* **catch_log_exceptions**: If set to true, all exceptions thrown in the doctrine event subscriber will be caught and logged.
* **index_name_prefix**: If set, this will add a prefix to all the index names (Useful if you want to setup multiple environments within the same Algolia app)
* **connection_timeout**: If set, this will set connection timeout to Algolia (in seconds)


# Mapping entities to Algolia indexes



Mapping an entity type to an Algolia index allows you to keep it in sync with Algolia, i.e. the operations involving mapped entities on your local database are mirrored on the Algolia indexes. Indexation is automatic by default, but can be made manual if needed.

Currently, mapping is only possible with annotations.

## Indexing entity properties or methods

The `Attribute` annotation marks a field or method for indexing by Algolia.

All annotations are defined in the `Algolia\AlgoliaSearchBundle\Mapping\Annotation` namespace.

Below is an example of an indexed field and an indexed property:

```php
namespace MyCoolAppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * Product
 *
 * @ORM\Entity
 *
 */
class Product
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @Algolia\Attribute
     *
     */
    protected $name;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="decimal", nullable=true)
     *
     */
    protected $price;

    /**
     * @Algolia\Attribute
     */
    public function getNameAndPrice()
    {
        return $this->name . " - " . $this->price;
    }
}
```

For optimal performance, please note that:
- the properties you index should be mapped properties, i.e. properties that Doctrine is aware of.
- the methods you index should be cheap to compute (simple concatenations, simple conditions...) *and* depend only on properties that Doctrine is aware of or on other methods that satisfy this second condition.

Abiding by the two rules above ensures that no useless update operations on Algolia servers are performed, and that all useful updates are performed.
If you map something that is not known to Doctrine to an Algolia field, then the engine has no way to know when it changed, which may lead to useless updates and/or unexpected results.

Since we can't enforce the 2 conditions above automatically it is the programmer's responsibility to verify them.

The names of the fields to be used on Algolia's servers are deduced automatically from the names of the fields or properties being indexed:
- for indexed properties, the Algolia name is the name of the property
- for indexed methods, the Algolia name is the name of the method, minus the leading "get" if the method starts with "get" then an uppercase letter. The first letter of the resulting name is converted to lower case.

You can override the Algolia name by setting the `algoliaName` argument in the annotation declaration, like this:

```php
    /**
     * @Algolia\Attribute(algoliaName="myCustomFieldName")
     */
    public function getNameAndPrice()
    {
        return $this->name . " - " . $this->price;
    }
```

By default the bundle will take the primary keys of your model to create the algolia objectID if you want to override this you can use one or more Id annotations.

```php
namespace MyCoolAppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * Product
 *
 * @ORM\Entity
 *
 */
class Product
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Algolia\Id
     * @Algolia\Attribute
     *
     */
    protected $name;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="decimal", nullable=true)
     *
     */
    protected $price;
}
```

In this example id will not be use as the objectId, instead name will be use.

**When you make changes to the mappings, you need to [re-index your entities](#reindexing-whole-collections) to reflect the changes in Algolia.**

## Autoindexing vs Manual Indexing
By default, mapped entities are automatically indexed on Algolia's servers using Doctrine's lifecycle events (synchronization is made during the onFlush and postFlush events).

You can change this behaviour with the `Index` annotation and the `autoIndex` parameter, like this:

```php
/**
 * Product
 *
 * @ORM\Entity
 * @Algolia\Index(autoIndex=false)
 *
 */
class Product
{
    // ...
}
```

Now, to index a `Product` entity, you will need to manually index it, example:

```php
// This assumes we're in a Controller,
// the Algolia indexer is provided as a Service.
$product = new Product();
$product->setName("Searching With Algolia");
$em = $this->getEntityManager();
$em->persist($product);
$em->flush();
$this->get('algolia.indexer')->getManualIndexer($em)->index($product);
```

(With autoindexing enabled, the product would have been indexed by the `$em->flush()` call)

The `ManualIndexer` class also provides the `unIndex` method to manually un-index entities and the `reIndex` method to re-index a whole collection.
Please see [the methods' comments](https://github.com/algolia/AlgoliaSearchBundle/blob/master/Indexer/ManualIndexer.php) for more info.

## Per environment indexing

By default, Algolia index names are suffixed with the name of the current application environment.

To disable this feature, use the `perEnvironment` option of the `Index` annotation:

```php
/**
 * Product
 *
 * @ORM\Entity
 * @Algolia\Index(perEnvironment=false)
 *
 */
class Product
{
    // ...
}
```

## Conditional indexing

It is often useful to skip indexing an entity based on some condition.

To this end, we provide the `IndexIf` annotation. This annotation can only be used on methods, and the methods should only depend on fields the ORM is aware of. Example:

```php
/**
 * Product
 *
 * @ORM\Entity
 * @Algolia\Index(perEnvironment=false)
 *
 */
class Product
{
    /**
     * @Algolia\IndexIf
     */
    public function isPublished()
    {
        return $this->published;
    }
}
```

You can have several `IndexIf` conditions, in which case the record is indexed if they *all* return true. It may be better for readability to keep only one such annotation though.

## Index settings

You can optionally specify your index settings directly in the `Index` annotation.

```php
/**
 *
 * @ORM\Entity
 *
 * @Algolia\Index(
 *     searchableAttributes = {"name", "description", "url"},
 *     customRanking = {"desc(vote_count)", "asc(name)" }
 *     [...]
 * )
 *
 */
class Product
{
}
```

The index settings are **not** automatically synchronized with Algolia but we provide a command line command to do it:

```bash
php app/console algolia:settings # show the local settings that are not applied to the Algolia indexes
php app/console algolia:settings --push # push the configuration changes to Algolia servers
```


# Retrieving entities



## Performing a raw search

You can retrieve raw results from Algolia indexes using the `rawSearch` method of the indexer:

```php
$this->get('algolia.indexer')->rawSearch('SomeIndexName', 'a query string');
```

This will return an array of hits, wrapped inside of a [SearchResult](https://github.com/algolia/AlgoliaSearchBundle/blob/master/SearchResult/SearchResult.php) instance.

This will not connect to the local database.

## Performing a native search

You can retrieve Doctrine entities from Algolia indexes using the `search` method of the indexer:

```php
$this->get('algolia.indexer')->search(
    $this->getEntityManager(),
    'MyCoolBundle:Product',
    'a query string'
);
```

This will return an array of hits, wrapped inside of a [SearchResult](https://github.com/algolia/AlgoliaSearchBundle/blob/master/SearchResult/SearchResult.php) instance.
Hits will be instance of the `Product` class, fetched from the local database.

Please note that since we need to access the local database here contrary to the `rawSearch` call you need to pass the `EntityManager`, which adds an argument.


# Reindexing



## Reindexing whole collections

You can re-index collections programmatically using the `reIndex` method of the `ManualIndexer` class (`$this->get('algolia.indexer')->getManualIndexer($this->getEntityManager())->reIndex('SomeBundle:EntityName')`), but you can also very easily do it using a simple console command:

```bash
php app/console algolia:reindex SomeBundle:EntityName
```

By default, a temporary index is created, the indexation is performed on the temporary index, and then the index is moved atomically to the target index.

You can re-index in place by passing the `--unsafe` option. Please note that in unsafe mode outdated entities will not be un-indexed.


# Running the tests



## Running the tests

Rename the test `parameters.yml.travis` file to `parameters.yml`, customize the settings with the correct database settings and Algolia API settings.

Please note that by default Algolia credentials are loaded from environment variable (see `algolia.get_credentials_from_env`).

Then run:

```bash
php vendor/bin/phpunit -c Tests
```



