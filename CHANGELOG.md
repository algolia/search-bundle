CHANGELOG
=========

3.1.0
-----

* Introduce `batchSize` config key (default: 500) - PR [#208](https://github.com/algolia/search-bundle/pull/208)
    
    This config allow you create smaller or bigger batch calls to Algolia. The same config is used by doctrine in the ImportCommand.
    
* Feature: Index entities conditionally (using a dedicated method) - PR [#210](https://github.com/algolia/search-bundle/pull/210)

    Add new `index_if` configuration key for indices.
    This should be the path to a property in the entity which
    evaluates to true if the item should be indexed and false to 
    bypass indexing or remove existing object from the index.

    Example:
        - indices:
            - name: posts
              class: App\Entity\Post
              index_if: isPublished

* Better support for Symfony 3.4 projects with the old folder structure

3.0.1
-----

* Dynamically retrieve object manager for ImportCommand - PR [#203](https://github.com/algolia/search-bundle/pull/203)

🎉 3.0.0 🎉
----------

Version 3 is a complete rewrite but the upgrade is straight forward.
Follow []the upgrade guide](https://github.com/algolia/search-bundle/blob/master/UPGRADE-3.0.md) for an easy step-by-step upgrade.

This version requires Symfony 3.4+

2.2.0
-----

- Introduce `algolia.connection_timeout` parameter to override default timeout of the Algolia PHP client

2.1.0
-----

- Allow to use `searchableAttributes` instead of `attributesToIndex`
- Allow to use `replicas` instead of slaves

2.0.0
-----

- Move to PSR-2 standards
- Deprecate php below 5.6

1.0.8
-----

- Fixes instantiating entities with constructor args

1.0.7
-----

- Bug Fixing

1.0.6
-----

- Improve handling of relations

1.0.5
-----

- Handle collection when creating the record.

1.0.4
-----

- Quick Fix get_class that gets the proxy instead of the Entity

1.0.3
-----

- Upgraded the underlying algoliasearch-client-php dependency

1.0.2
-----

- Minor fixes

1.0.1
-----

- MIT License
