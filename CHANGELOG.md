CHANGELOG
=========

UNRELEASED
----------

3.4.0
----------

* Feature: Aggregators - Multiple entities in the same index - PR [#257](https://github.com/algolia/search-bundle/pull/257)

    An aggregator object allows to aggregate more than
    one entity type in the same index. With aggregators,
    you can easily provide a better search experience
    since the search results will contain content from
    various entities.

* Fix: Issue in `index_if` functionality in update cases - PR [#257](https://github.com/algolia/search-bundle/pull/257)

    Previously, if the developer used the `IndexManager` directly,
    there was a risk that the entity may not be removed from the remote
    index. Now, the verification is done directly in the `IndexManager`,
    fixing that issue.


3.3.3
----------

 * Fix deprecation notices with Symfony 4.2 - PR [#273](https://github.com/algolia/search-bundle/pull/273)

3.3.2
----------

 * Fix bug in `IndexManager::count` to take parameters into account - PR [#260](https://github.com/algolia/search-bundle/pull/260)

    Note that this bug fix is backward compatible but should be clean up
    when we release a next major version.

3.3.1
----------

* Little optimization, AlgoliaEngine will serialize an Entity only once - PR [#255](https://github.com/algolia/search-bundle/pull/255)


3.3.0
----------

* Make Algolia Client lazy - PR [#251](https://github.com/algolia/search-bundle/pull/251)

    If you didn't set the `ALGOLIA_APP_ID` and `ALGOLIA_API_KEY` env variables
    you will only get an error message when the client is used (a method is called),
    not when its injected and not used.

    Note: This requires that you install `ocramius/proxy-manager` and
    `symfony/proxy-manager-bridge` packages


* Removed connection attribute for Doctrine Event Subscribers - PR [#248](https://github.com/algolia/search-bundle/pull/248)

3.2.0
----------

* Support JMS Serializer - PR [#225](https://github.com/algolia/search-bundle/pull/225)

    If you'd rather use the JMS Serializer instead of the default Symfony serializer,
    you can set `serializer: jms_serializer` in `config/packages/algolia_search.yaml`.
    Note that the @Groups annotation isn't supported.

* `NullEngine` was improved to remove warning - Issue [#234](https://github.com/algolia/search-bundle/issues/234)

* The entire test suite was refactored - PR [#236](https://github.com/algolia/search-bundle/pull/236)

3.1.2
-----

* Fix circular reference issue when removing entities - PR [#227](https://github.com/algolia/search-bundle/pull/227)

3.1.1
-----

* Lazy load commands - PR [#218](https://github.com/algolia/search-bundle/pull/218)

    Symfony 3.4 introduced an easy way to lazy load all commands. We can avoid instantiating
    the Algolia Client if it's not necessary.
    This should fix #199

* Do not register Event Subscriber if there are no event to listen to - PR [#219](https://github.com/algolia/search-bundle/pull/219)

    If you don't listen to any doctrine event using `doctrineSubscribedEvents: []`,
    the subscriber will not be registered to avoid instantiation the IndexManager and
    the Algolia client.

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
