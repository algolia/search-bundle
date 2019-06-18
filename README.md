<p align="center">
  <a href="https://www.algolia.com">
    <img alt="Algolia for Symfony" src="https://raw.githubusercontent.com/algolia/algoliasearch-client-common/master/banners/symfony.png" >
  </a>

  <h4 align="center">
  	The perfect starting point to integrate 
  	<a href="https://algolia.com" target="_blank">Algolia</a> 
  	within your Symfony project
  </h4>

  <p align="center">
    <a href="https://travis-ci.org/algolia/search-bundle"><img src="https://travis-ci.org/algolia/search-bundle.svg?branch=master" alt="Build Status"></a>
    <a href="https://packagist.org/packages/algolia/search-bundle"><img src="https://poser.pugx.org/algolia/search-bundle/downloads" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/algolia/search-bundle"><img src="https://poser.pugx.org/algolia/search-bundle/v/stable" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/algolia/search-bundle"><img src="https://poser.pugx.org/algolia/search-bundle/license" alt="License"></a>
  </p>
</p>

<p align="center">
  <a href="https://www.algolia.com/doc/framework-integration/symfony/getting-started" target="_blank">Documentation</a>  •
  <a href="https://github.com/algolia/algoliasearch-client-php" target="_blank">PHP</a>  •
  <a href="https://github.com/algolia/scout-extended" target="_blank">Laravel</a>  •
  <a href="https://discourse.algolia.com" target="_blank">Community Forum</a>  •
  <a href="http://stackoverflow.com/questions/tagged/algolia" target="_blank">Stack Overflow</a>  •
  <a href="https://github.com/algolia/search-bundle/issues" target="_blank">Report a bug</a>  •
  <a href="https://www.algolia.com/support" target="_blank">Support</a>
</p>

## ✨ Features

 * **Compatible** with Symfony 3.4 LTS and Symfony 4.0 (and later).
 * **Simple**: You can get started with only 5 lines of YAML
 * **Extensible**: It lets you easily replace services by implementing Interfaces
 * **Standard**: It leverages Normalizers to convert entities for indexing
 * **Dev-friendly**: It lets you disable HTTP calls easily (while running tests, for example)
 * **Future-ready**: It lets you unsubscribe from doctrine events easily to use a messaging/queue system.
 
## 💡 Getting Started

First, install Algolia Search Bundle Integration via the composer package manager:

```bash
composer require algolia/search-bundle
```

You will also need to provide the Algolia App ID and Admin API key. By default, they
are loaded from environment variables `ALGOLIA_APP_ID` and `ALGOLIA_API_KEY`.

If you use `.env` config file, you can set them there.

```yml
ALGOLIA_APP_ID=XXXXXXXXXX
ALGOLIA_API_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

If you don't use environment variables, you can set them in your `parameters.yml`.

```yml
parameters:
    env(ALGOLIA_APP_ID): XXXXXXXXXX
    env(ALGOLIA_API_KEY): xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

## Indexing data

First, we need to define which entities should be indexed in Algolia.
Each entry under the `indices` config key must contain at least the 2 following attributes:

* `name` is the canonical name of the index in Algolia
* `class` is the full name of the entity to index

Example:

```yaml
algolia_search:
  indices:
    - name: posts
      class: App\Entity\Post
```

### Via CLI

Once your `indices` config is ready, you can use the built-in console command
to batch import all existing data.

```sh
# Import all indices
php bin/console search:import

# Choose what indices to reindex by passing the index name
php bin/console search:import --indices=posts,comments
```

Before re-indexing everything, you may want to clear the index first,
see [how to remove data](https://www.algolia.com/doc/framework-integration/symfony/indexing/?language=php#removing-manually).

## Simple Search

In this example we'll search for posts. The `search` method will query Algolia
to get matching results and then will create a doctrine collection. The data are
pulled from the database (that's why you need to pass the Doctrine Manager).

Notice that I use `$this->indexManager` here because your IndexManager must be
injected in your class. [Read how to inject the IndexManager here](https://www.algolia.com/doc/framework-integration/symfony/getting-started/#injecting-services).

```php
$em = $this->getDoctrine()->getManagerForClass(Post::class);

$posts = $this->indexManager->search('query', Post::class, $em);
```

For full documentation, visit the **[Algolia Symfony Search Bundle](https://www.algolia.com/doc/framework-integration/symfony/getting-started/)**.

## 📄 License

Algolia Symfony Search Bundle is an open-sourced software licensed under the [MIT license](LICENSE.md).