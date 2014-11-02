AlgoliaSearchSymfonyDoctrineBundle
==================================

This Symfony bundle provides an easy way to integrate Algolia Search into your Symfony2 with Doctrine2 application.

[![Build Status](https://travis-ci.org/djfm/AlgoliaSearchSymfonyDoctrineBundle.svg?branch=master)](https://travis-ci.org/djfm/AlgoliaSearchSymfonyDoctrineBundle)

# Mapping entities to Algolia indexes

Mapping an entity type to an Algolia index allows you to keep it in sync with Algolia, i.e. the operations involving mapped entities on your local database are mirrored on the Algolia indexes. Indexation is automatic by default, but can be made manual if needed.

Currently, mapping is only possible with annotations.

## Indexing entity properties or methods

The `Field` annotation marks a field or method for indexing by algolia.

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

We can't enforce the 2 conditions above automatically, it is the programmer's responsibility to verify them.

The names of the fields to be used on Algolia's servers are deduced automatically form the names of the fields or properties being indexed:
- for indexed properties, the Algolia name is the name of the property
- for indexed methods, the Algolia name is the name of the method, minus the leading "get" if the method starts with "get" then an uppercase letter, with the first letter is converted to lower case.

You can override the Algolia name by setting the algoliaName argument in the annotation declaration, like this:

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

With autoindexing enabled, it would have been indexed by the `$em->flush()` call.

The `ManualIndexer` class also provides the `unIndex` method to manually un-index entities and the `reIndex` method to re-index a whole collection.
Please see [the methods' comments](Indexer/ManualIndexer.php) for more info.

## Indexing per environment

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

```
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