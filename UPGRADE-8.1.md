
## Upgrading from 8.0.0 to 8.1.0

The SearchBundle now uses the [Algolia PHP API Client v4](https://www.algolia.com/doc/libraries/php/v4/). This is a significant upgrade of the underlying SDK that introduces a couple of breaking changes.

Upgrade your `algolia/search-bundle` dependency to `^8.1` in your `composer.json` file and run `composer update algolia/search-bundle algolia/algoliasearch-client-php` in your terminal.


## SearchClient namespace

The `SearchClient` class has moved to a new namespace. If you inject or type-hint `SearchClient` in your own services, update the fully qualified class name.

Before:
```php
use Algolia\AlgoliaSearch\SearchClient;
```

After:
```php
use Algolia\AlgoliaSearch\Api\SearchClient;
```

The bundle's DI alias has been updated accordingly:

Before:
```yaml
services:
    App\Service\MyService:
        arguments:
            $client: '@Algolia\AlgoliaSearch\SearchClient'
```

After:
```yaml
services:
    App\Service\MyService:
        arguments:
            $client: '@Algolia\AlgoliaSearch\Api\SearchClient'
```

If you autowire by type-hint, update your constructor or method signatures:

Before:
```php
public function __construct(\Algolia\AlgoliaSearch\SearchClient $client) {}
```

After:
```php
public function __construct(\Algolia\AlgoliaSearch\Api\SearchClient $client) {}
```


## Request options format change

In v3, custom HTTP headers could be passed as flat top-level keys in `$requestOptions`. In v4, flat keys are **silently ignored**. HTTP headers must be nested under the `headers` key.

Before:
```php
$searchService->index($em, $entity, ['X-Forwarded-For' => '0.0.0.0']);
```

After:
```php
$searchService->index($em, $entity, [
    'headers' => ['X-Forwarded-For' => '0.0.0.0'],
]);
```

This applies to all `SearchService` methods that accept `$requestOptions`: `index()`, `remove()`, `clear()`, `delete()`, `search()`, `rawSearch()`, and `count()`.

The recognized top-level keys for `$requestOptions` in v4 are:

- `headers` — Custom HTTP headers (associative array)
- `queryParameters` — Extra URL query parameters (associative array)
- `body` — Extra body parameters (associative array)
- `readTimeout` — Read timeout in milliseconds (int)
- `writeTimeout` — Write timeout in milliseconds (int)
- `connectTimeout` — Connection timeout in milliseconds (int)


## List of changes in fully qualified namespaces

<div class="overflow-x-auto">
  <table>
    <thead>
      <tr>
        <th>8.0.0</th>
        <th>Breaking Change</th>
        <th>8.1.0</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><code>Algolia\AlgoliaSearch\SearchClient</code></td>
        <td>Moved</td>
        <td><code>Algolia\AlgoliaSearch\Api\SearchClient</code></td>
      </tr>
    </tbody>
  </table>
</div>


## List of updated public services names

<div class="overflow-x-auto">
  <table>
    <thead>
      <tr>
        <th>8.0.0</th>
        <th>Breaking Change</th>
        <th>8.1.0</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><code>Algolia\AlgoliaSearch\SearchClient</code> (autowiring alias)</td>
        <td>Renamed</td>
        <td><code>Algolia\AlgoliaSearch\Api\SearchClient</code></td>
      </tr>
    </tbody>
  </table>
</div>
