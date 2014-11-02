AlgoliaSearchSymfonyDoctrineBundle
==================================

This Symfony bundle provides an easy way to integrate Algolia Search into your Symfony2 with Doctrine2 application.

[![Build Status](https://travis-ci.org/djfm/AlgoliaSearchSymfonyDoctrineBundle.svg?branch=master)](https://travis-ci.org/djfm/AlgoliaSearchSymfonyDoctrineBundle)

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Content**

- [Setup](#setup)
  - [Setup composer](#setup-composer)
  - [Register the bundle](#register-the-bundle)
  - [Fill in your Algolia credentials](#fill-in-your-algolia-credentials)
- [Mapping entities to Algolia indexes](#mapping-entities-to-algolia-indexes)
  - [Indexing entity properties or methods](#indexing-entity-properties-or-methods)
  - [Autoindexing vs Manual Indexing](#autoindexing-vs-manual-indexing)
  - [Per environment indexing](#per-environment-indexing)
  - [Conditional indexing](#conditional-indexing)
  - [Advanced index settings](#advanced-index-settings)
- [Retrieving entities](#retrieving-entities)
  - [Performing a raw search](#performing-a-raw-search)
  - [Performing a native search](#performing-a-native-search)
- [Re-indexing whole collections](#re-indexing-whole-collections)
- [Running the tests](#running-the-tests)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# Setup

It's easy, I promise.

## Setup composer

Add this line to your composer.json file:
```json
"require": {
    ...
    "djfm/algolia-search-symfony-doctrine-bundle": "dev-master",
    ...
}
```

Then run `composer update`.

## Register the bundle

Add `Algolia\AlgoliaSearchSymfonyDoctrineBundle\AlgoliaAlgoliaSearchSymfonyDoctrineBundle()` to your application Kernel:
```php
$bundles = array(
    new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
    new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
    new Algolia\AlgoliaSearchSymfonyDoctrineBundle\AlgoliaAlgoliaSearchSymfonyDoctrineBundle(),
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

That's it!

# Mapping entities to Algolia indexes

Mapping an entity type to an Algolia index allows you to keep it in sync with Algolia, i.e. the operations involving mapped entities on your local database are mirrored on the Algolia indexes. Indexation is automatic by default, but can be made manual if needed.

Currently, mapping is only possible with annotations.

## Indexing entity properties or methods

The `Field` annotation marks a field or method for indexing by Algolia.

All annotations are defined in the `Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation` namespace.

Below is an example of an indexed field and an indexed property:

```php
namespace MyCoolAppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation as Algolia;

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
     * @Algolia\Field
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
     * @Algolia\Field
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
     * @Algolia\Field(algoliaName="myCustomFieldName")
     */
    public function getNameAndPrice()
    {
        return $this->name . " - " . $this->price;
    }
```
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
Please see [the methods' comments](Indexer/ManualIndexer.php) for more info.

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

## Advanced index settings

You can optionally specify most of your index settings directly in the `Index` annotation. Most options supported by Algolia can be set this way.

Please see the [`Index` annotation class](Mapping/Annotation/Index.php) for more details.

The advanced settings are not automatically synchronized with Algolia, but we provide a command line command to do it:

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

This will return an array of hits, wrapped inside of a [SearchResult](SearchResult/SearchResult.php) instance.

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

This will return an array of hits, wrapped inside of a [SearchResult](SearchResult/SearchResult.php) instance.
Hits will be instance of the `Product` class, fetched from the local database.

Please note that since we need to access the local database here contrary to the `rawSearch` call you need to pass the `EntityManager`, which adds an argument.

# Re-indexing whole collections
You can re-index collections programmatically using the `reIndex` method of the `ManualIndexer` class (`$this->get('algolia.indexer')->getManualIndexer($this->getEntityManager())->reIndex('SomeBundle:EntityName')`), but you can also very easily do it using a simple console command:

```bash
php app/console algolia:reindex SomeBundle:EntityName
```

By default, a temporary index is created, the indexation is performed on the temporary index, and then the index is moved atomically to the target index.

You can re-index in place by passing the `--unsafe` option. Please note that in unsafe mode outdated entities will not be un-indexed.

# Running the tests
Rename the test [parameters.yml.dist](Tests/config/parameters.yml.dist) file to `parameters.yml`, customize the settings with the correct database settings and Algolia API settings, then run:
```bash
php vendor/bin/phpunit -c Tests
```