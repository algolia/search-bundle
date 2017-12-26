UPGRADE FROM 2.x to 3.0
=======================

Package
-------
The bundle has been renamed, be sure to change in your `composer.json` the name of the dependency:

Before:
```json
"algolia/algolia-search-bundle"
```

After:
```json
"algolia/search-bundle"
```

Bundle
------

Because of the bundle renaming, you need to update your `AppKernel.php` to 

Before:
```php
new Algolia\AlgoliaSearchBundle\AlgoliaAlgoliaSearchBundle(),
```

After:
```php
new Algolia\SearchBundle\AlgoliaSearchBundle(),
```

Config
------

Before:

```yml
algolia:
    application_id: xxxxxx
    api_key: xxxxxxxxxxxxxxxxx
```

After:

```yml
algolia_search:
    indices:
        - name: posts
          class: App\Entity\Post
```

Note that if you previously had a `prefix` configured, you have to change the prefix:

Before:

```yml
algolia:
    prefix: foo
```

After:

```yml
algolia_search:
    prefix: foo_
```

The "Per environment indexing" was dropped. If you relied on this to separate your index (ex: prod vs dev) you need to apply this changes:

Before:

```yml
# the per environment indexing feature was enabled by default
```

After:

```yml
// config.yml
algolia_search:
    indices:
        - name: posts
          class: App\Entity\Post
```

```yml
// config_dev.yml
algolia_search:
    prefix: dev_
```

Mapping
------- 

The data is normalized using the Symfony Serializer component. You should read the [documentation](README.md) to learn how to create/use normalizer.
Because of that change, you have to **remove all `@Algolia` annotations** you previously put on your entities.

Before:
```php
/**
 * @ORM\Column(name="description", type="text")
 * @Algolia\Attribute
 */
protected $description;
```

After:
```php
/**
 * @ORM\Column(name="description", type="text")
 */
protected $description;
```


Command
-------

The indexation command has been renamed.

Before:
```
bin/console algolia:reindex
```

After:
```
bin/console search:import
```



