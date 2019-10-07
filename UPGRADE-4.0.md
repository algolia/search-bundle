
## Upgrading from 3.4.0 to 4.0.0

The new SearchBundle is now based on our [PHP Client v2](https://www.algolia.com/doc/api-client/getting-started/install/php/). In addition to providing you with all the new features our PHP Client v2 has to offer, it has been redesigned to give the most intuitive and easy-to-use experience. For that reason, we tried to make the architecture as clear as possible and we got rid of the Search Engine-agnostic feature.
To better reflect this choice, `AlgoliaEngine` and `IndexManager` classes have been renamed to `Engine` and `SearchService`.

We attempt to document every possible breaking change. Since some of these breaking changes are in obscure parts of the API Client, only a portion of these changes may affect your application.

Upgrade your `algolia/search-bundle` dependency from `3.4` to `^4.0` in your `composer.json` file and run `composer 
update` in your terminal.

## Removing the Search Engine-agnostic feature

We decided as an effort to give you the best possible experience with Algolia to get rid of the possibility to use the bundle with any Search Engine. 
If you were using our bundle that way please keep on using the v3. Be aware however that new features will only be 
added to the v4.


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
        <td>Renamed</td>
        <td><code>Algolia\SearchBundle\Engine</code></td>
    </tr>
    <tr>
        <td><code>Algolia\SearchBundle\IndexManager</code></td>
        <td>Renamed</td>
        <td><code>Algolia\SearchBundle\SearchService</code></td>
    </tr>
    <tr>
        <td><code>Algolia\SearchBundle\Engine\NullEngine</code></td>
        <td>Removed</td>
        <td><strong>For testing purposes only</strong> use <code>Algolia\SearchBundle\SearchServiceInterface</code></td>
    </tr>
</tbody>
</table>
</div>


## List of classes that became final

* `Algolia\SearchBundle\AlgoliaSearchBundle`
* `Algolia\SearchBundle\Command\SearchClearCommand`
* `Algolia\SearchBundle\Command\SearchImportCommand`
* `Algolia\SearchBundle\Command\SearchSettingsBackupCommand`
* `Algolia\SearchBundle\Command\SearchSettingsCommand`
* `Algolia\SearchBundle\Command\SearchSettingsPushCommand`
* `Algolia\SearchBundle\Engine`
* `Algolia\SearchBundle\EventListener\SearchIndexerSubscriber `
* `Algolia\SearchBundle\SearchableEntity`
* `Algolia\SearchBundle\SettingsManager`

If you were extending those classes, please update your code accordingly.


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
        <td><code>Algolia\SearchBundle\Engine</code></td>
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

Moreover, the `search.client` service is now public. You don't have to alias it manually anymore, and that means 
you can make direct calls to Algolia client every time you need to perform advanced operations (like managing API keys, 
indices and such).

## SearchService

`IndexManager` has been renamed to `SearchService`. This is the only service you will need to interact with Algolia.
However, to avoid direct calls to the API during your tests, we created the `SearchServiceInterface`. Please use it only
for testing purposes.

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
use Algolia\SearchBundle\SearchService;

class ExampleController extends Controller
{

    protected $searchService;

    public function __construct(SearchService $searchService)
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
        <td><code>index($entities, ObjectManager $objectManager)<br><br><strong>@return</strong> array&lt;string, 
        int&gt;</code></td>
        <td>Changed</td>
        <td><code>index($entities, ObjectManager $objectManager, $requestOptions = [])
        <br><br><strong>@return</strong> array&lt;int, array&lt;string, 
        \Algolia\AlgoliaSearch\Response\AbstractResponse&gt;&gt;</code></td>
    </tr>
    <tr>
        <td><code>remove($entities, ObjectManager $objectManager)<br><br><strong>@return</strong> array&lt;string, 
        int&gt;</code></td>
        <td>Changed</td>
        <td><code>remove($entities, ObjectManager $objectManager, $requestOptions = [])
        <br><br><strong>@return</strong> array&lt;int, array&lt;string, 
        \Algolia\AlgoliaSearch\Response\AbstractResponse&gt;&gt;</code></td>
    </tr>
    <tr>
        <td><code>clear($className)<br><br><strong>@return</strong> boolean</code></td>
        <td>Changed</td>
        <td><code>clear($className)<br><br><strong>@return</strong> \Algolia\AlgoliaSearch\Response\AbstractResponse</code></td>
    </tr>
    <tr>
        <td><code>delete($className)<br><br><strong>@return</strong> boolean</code></td>
        <td>Changed</td>
        <td><code>delete($className)<br><br><strong>@return</strong> \Algolia\AlgoliaSearch\Response\AbstractResponse</code></td>
    </tr>
    <tr>
        <td><code>search($query, $className, ObjectManager $objectManager, $page = 1, $nbResults = null, array 
        $parameters = [])<br><br><strong>@return</strong> array&lt;int, object&gt;</code></td>
        <td>Changed</td>
        <td><code>search($query, $className, ObjectManager $objectManager, $requestOptions = [])
        <br><br><strong>@return</strong> array&lt;int, object&gt;</code></td>
    </tr>
    <tr>
        <td><code>rawSearch($query, $className, $page = 1, $nbResults = null, array $parameters = [])
        <br><br><strong>@return</strong> array&lt;string, int|string|array&gt;</code></td>
        <td>Changed</td>
        <td><code>rawSearch($query, $className, $requestOptions = [])<br><br><strong>@return</strong> array&lt;
        string, int|string|array&gt;</code></td>
    </tr>
    <tr>
        <td><code>count($query, $className, array $parameters = [])<br><br><strong>@return</strong> int</code></td>
        <td>Changed</td>
        <td><code>count($query, $className, $requestOptions = [])<br><br><strong>@return</strong> int</code></td>
    </tr>
</tbody>
</table>
</div>

Most of the methods now return Algolia `AbstractResponses`. That means you can take advantage of the [`->wait()`](https://www.algolia.com/doc/api-reference/api-methods/wait-task/) method 
to wait for your tasks to be completely handled by Algolia Engine before moving on. The only drawback is that you have to format yourself the Engine's responses.

The last argument in the methods calling the Engine is always `$requestOptions`. Use it to pass any optional arguments, such as `autoGenerateObjectIDIfNotExist` or `hitsPerPage`.

Here are a few examples:

```php
# v3
$result = $this->indexManager->index(
    $searchablePosts,
    $this->get('doctrine')->getManager(),
);

# v4
$result = $this->searchService->index(
    $searchablePosts,
    $this->get('doctrine')->getManager(),
    [
        'autoGenerateObjectIDIfNotExist' => true,
    ]
);
```

```php
# v3
$result = $this->indexManager->remove(
    $searchablePosts,
    $this->get('doctrine')->getManager(),
);

# v4
$result = $this->searchService->remove(
    $searchablePosts,
    $this->get('doctrine')->getManager(),
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
    [
        'page'                 => 0,
        'hitsPerPage'          => 20,
        'attributesToRetrieve' => [
            'title',
        ],
    ]
);
```

## Miscellaneous

We also encourage you to take a look at the [PHP Client v2 upgrade guide](https://www.algolia.com/doc/api-client/getting-started/upgrade-guides/php/) to get acquainted with all the new features deployed in this version.
