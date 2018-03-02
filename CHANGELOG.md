CHANGELOG
=========

3.1.0
-----

- Introduce `batchSize` config key (default: 500) - PR [#208](https://github.com/algolia/search-bundle/pull/203)
    
    This config allow you create smaller or bigger batch calls to Algolia. The same config is used by doctrine in the ImportCommand.
    

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
