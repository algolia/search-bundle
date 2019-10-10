
## Upgrading from 3.4.0 to 4.0.0

The new SearchBundle is now based on our [PHP Client v2](https://www.algolia.com/doc/api-client/getting-started/install/php/). In addition to providing you with all the new features our PHP Client v2 has to offer, it has been redesigned to give the most intuitive and easy-to-use experience. 
For that reason, we tried to make the architecture as clear as possible and we got rid of the Search Engine-agnostic feature. As an example, `AlgoliaEngine` and `IndexManager` classes have been merge into one single `SearchService` class.
Also, the Algolia Client is now public. Its methods signatures and return types have however changed from v1 to v2. Please read carefully our [PHP Client v2 upgrade guide](https://www.algolia.com/doc/api-client/getting-started/upgrade-guides/php/) to get a list of thoses changes.

We attempt to document every possible breaking change. Since some of these breaking changes are in obscure parts of the API Client, only a portion of these changes may affect your application.

Upgrade your `algolia/search-bundle` dependency from `3.4` to `^4.0` in your `composer.json` file and run `composer update algolia/search-bundle` in your terminal.


## Miscellaneous

We strongly encourage you to take a look at the [PHP Client v2 upgrade guide](https://www.algolia.com/doc/api-client/getting-started/upgrade-guides/php/) to get acquainted with the new features deployed in this version.


## Features ✨
* Better DX with the usage of our PHP Client v2 (entire redesign transparent to end user to make it faster and easier to use, lots of new methods among which `copyIndex`, `replaceAllObjects`, better Exceptions, ...)
* All the methods from the `SearchService` are *waitable*. You can wait for the engine to finish its task before moving on to a new one. See [`->wait()`](https://www.algolia.com/doc/api-reference/api-methods/wait-task/) method for more info.
* Refactoring and simplification of the bundle with the merge of the two classes `AlgoliaEngine` and `IndexManager` to one `SearchService`. The bundle is now clearer and easier to browse.
* Better doc blocks to improve the public API and auto-completion.
* The possibility to additional arguments and options thanks to the `$requestOptions` argument, available in all `SearchService` methods.
* Algolia Client is now public by default. You can make direct calls to it every time you need to perform advanced operations (like managing API keys, indices and such).


## List of changes in fully qualified namespaces
<div class="overflow-x-auto">
<table>
<thead>
<tr>
<th>3.4.0</th>
<th>Breaking Change</th
<th>4.0.0</th>
</tr>
</thead>
<tbody>
    <tr>
        <td><code>Algolia\SearchBundle\Engine\AlgoliaEngine</code></td>
        <td>Removed</td>
        <td></td>
    </tr>
    <tr>
        <td><code>Algolia\SearchBundle\IndexManager</code></td>
        <td>Renamed</td>
        <td><code>Algolia\SearchBundle\SearchService</code></td>
    </tr>
    <tr>
        <td><code>Algolia\SearchBundle\Engine\NullEngine</code></td>
        <td>Removed</td>
        <td>For testing purposes use <code>Algolia\SearchBundle\SearchServiceInterface</code> by mocking it or extending it and overriding the `search.service` in your test config (see https://symfony.com/doc/current/configuration.html#configuration-environments)</td>
    </tr>
</tbody>
</table>
</div>


## List of classes that became internal

The following classes are now internal and may not respect semantic versioning. Those classes are not meant to be 
used directly and may be up to changes in minor versions.

* `Algolia\SearchBundle\Command\SearchClearCommand`
* `Algolia\SearchBundle\Command\SearchImportCommand`
* `Algolia\SearchBundle\Command\SearchSettingsBackupCommand`
* `Algolia\SearchBundle\Command\SearchSettingsCommand`
* `Algolia\SearchBundle\Command\SearchSettingsPushCommand`
* `Algolia\SearchBundle\DependencyInjection\AlgoliaSearchExtension`
* `Algolia\SearchBundle\DependencyInjection\Configuration`
* `Algolia\SearchBundle\Engine`
* `Algolia\SearchBundle\EventListener\SearchIndexerSubscriber`
* `Algolia\SearchBundle\Searchable`
* `Algolia\SearchBundle\SearchableEntity`
* `Algolia\SearchBundle\SettingsManager`


## List of deleted interfaces and final classes you should use instead
<div class="overflow-x-auto">
<table>
<thead>
<tr>
<th>3.4.0</th>
<th>Breaking Change</th>
<th>4.0.0</th>
</tr>
</thead>
<tbody>
    <tr>
        <td><code>Algolia\SearchBundle\Settings\SettingsManagerInterface</code></td>
        <td>Removed</td>
        <td><code>Algolia\SearchBundle\Settings\SettingsManager</code></td>
    </tr>
    <tr>
        <td><code>Algolia\SearchBundle\Engine\EngineInterface</code></td>
        <td>Removed</td>
        <td></td>
    </tr>
    <tr>
        <td><code>Algolia\SearchBundle\Engine\IndexManagerInterface</code></td>
        <td>Removed</td>
        <td><code>Algolia\SearchBundle\SearchService</code></td>
    </tr>
</tbody>
</table>
</div>


## List of updated public services names
<div class="overflow-x-auto">
<table>
<thead>
<tr>
<th>3.4.0</th>
<th>Breaking Change</th>
<th>4.0.0</th>
</tr>
</thead>
<tbody>
    <tr>
        <td><code>search.index_manager</code></td>
        <td>Renamed</td>
        <td><code>search.service</code></td>
    </tr>
    <tr>
        <td><code>algolia_client</code><br>(setup publicly manually, you may not have been using it)</td>
        <td>Renamed</td>
        <td><code>search.client</code></td>
    </tr>
</tbody>
</table>
</div>

Moreover, the `search.client` service is now public.

## SearchService

`IndexManager` has been renamed to `SearchService`. This is the only service you will need to interact with Algolia.
However, to avoid direct calls to the API during your tests, we created the `SearchServiceInterface`. Please use it only
for testing purposes, as this interface is targeted for changes in minor versions.

Previously, to get started:

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

Now, in the `4.0.0` you should update your code to:

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Algolia\SearchBundle\SearchServiceInterface;

class ExampleController extends Controller
{

    protected $searchService;

    public function __construct(SearchServiceInterface $searchService)
    {
        $this->searchService = $searchService;
    }
}
```

##### List of Method Signature Changes

<div class="overflow-x-auto">
<table>
<thead>
<tr>
<th>3.4.0</th>
<th>Breaking Change</th>
<th>4.0.0</th>
</tr>
</thead>
<tbody>
    <tr>
        <td><code>index($entities, ObjectManager $objectManager): array&lt;string, int&gt;</code></td>
        <td>Changed</td>
        <td><code>index($entities, ObjectManager $objectManager, $requestOptions = []): array&lt;int, array&lt;string, \Algolia\AlgoliaSearch\Response\AbstractResponse&gt;&gt;</code></td>
    </tr>
    <tr>
        <td><code>remove($entities, ObjectManager $objectManager): array&lt;string, int&gt;</code></td>
        <td>Changed</td>
        <td><code>remove($entities, ObjectManager $objectManager, $requestOptions = []): array&lt;int, array&lt;string, \Algolia\AlgoliaSearch\Response\AbstractResponse&gt;&gt;</code></td>
    </tr>
    <tr>
        <td><code>clear($className): boolean</code></td>
        <td>Changed</td>
        <td><code>clear($className): \Algolia\AlgoliaSearch\Response\AbstractResponse</code></td>
    </tr>
    <tr>
        <td><code>delete($className): boolean</code></td>
        <td>Changed</td>
        <td><code>delete($className): \Algolia\AlgoliaSearch\Response\AbstractResponse</code></td>
    </tr>
    <tr>
        <td><code>search($query, $className, ObjectManager $objectManager, $page = 1, $nbResults = null, array $parameters = []): array&lt;int, object&gt;</code></td>
        <td>Changed</td>
        <td><code>search($query, $className, ObjectManager $objectManager, $requestOptions = []): array&lt;int, object&gt;</code></td>
    </tr>
    <tr>
        <td><code>rawSearch($query, $className, $page = 1, $nbResults = null, array $parameters = []): array&lt;string, int|string|array&gt;</code></td>
        <td>Changed</td>
        <td><code>rawSearch($query, $className, $requestOptions = []): array&lt;string, int|string|array&gt;</code></td>
    </tr>
    <tr>
        <td><code>count($query, $className, array $parameters = []): int</code></td>
        <td>Changed</td>
        <td><code>count($query, $className, $requestOptions = []): int</code></td>
    </tr>
</tbody>
</table>
</div>

Most of the methods now return Algolia `AbstractResponse`. That means you can take advantage of the [`->wait()`](https://www.algolia.com/doc/api-reference/api-methods/wait-task/) method 
to wait for your tasks to be completely handled by Algolia Engine before moving on.

To have the most consistent, predictable, and future-proof method signature, we followed three rules:

- All required parameters have a single argument each
- All optional arguments are passed in an array (or RequestOptions object), as the last argument, and is called `$requestOptions`
- The client never sets any default values

Here are a few examples:


```php
# v3
$result = $this->indexManager->remove(
    $searchablePosts,
    $this->get('doctrine')->getManager(),
    // you could not pass requestOptions
);

# v4
$result = $this->searchService->remove(
    $searchablePosts,
    $this->get('doctrine')->getManager(),
    // here you can pass any requestOptions, for example 'X-Forwarded-For', 'Algolia-User-Id'...
    [
        'X-Forwarded-For' => '0.0.0.0',
    ]
);
```

```php
# v3
$result = $this->indexManager->search(
    'foo', 
    Post::class, 
    $this->get('doctrine')->getManager(),
    // the optional page and hitsPerPage parameters were passed separately
    1,
    20,
    'attributesToRetrieve' => [
        'title',
    ],
);

# v4
$result = $this->searchService->search(
    'foo', 
    Post::class, 
    $this->get('doctrine')->getManager(),
    // all the optional parameters are now sent as once in the $requestOptions
    [
        'page'                 => 0,
        'hitsPerPage'          => 20,
        'attributesToRetrieve' => [
            'title',
        ],
    ]
);
```
