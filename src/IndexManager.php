<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\Engine\EngineInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class IndexManager implements IndexingManagerInterface, SearchManagerInterface
{
    protected $engine;
    protected $indexConfiguration;
    protected $prefix;
    protected $nbResults;

    private $searchableEntities;
    private $classToIndexMapping;
    private $normalizer;

    public function __construct(NormalizerInterface $normalizer, EngineInterface $engine, array $indexConfiguration, $prefix, $nbResults)
    {
        $this->engine             = $engine;
        $this->indexConfiguration = $indexConfiguration;
        $this->prefix             = $prefix;
        $this->nbResults          = $nbResults;
        $this->normalizer         = $normalizer;

        $this->setSearchableEntities();
        $this->setClassToIndexMapping();
    }

    public function isSearchable($className)
    {
        if (is_object($className)) {
            $className = ClassUtils::getClass($className);
        }

        return in_array($className, $this->searchableEntities);
    }

    public function getSearchableEntities()
    {
        return $this->searchableEntities;
    }

    public function index($entities, ObjectManager $objectManager)
    {
        if (! is_array($entities)) {
            $entities = [$entities];
        }

        foreach (array_chunk($entities, 500) as $chunk) {
            $searchableEntities = [];

            foreach ($chunk as $entity) {
                $className = ClassUtils::getClass($entity);
                $this->assertIsSearchable($className);

                $searchableEntities[] = new SearchableEntity(
                    $this->getFullIndexName($className),
                    $entity,
                    $objectManager->getClassMetadata($className),
                    $this->normalizer
                );
            }

            $this->engine->update($searchableEntities);
        }

    }

    public function remove($entities, ObjectManager $objectManager)
    {
        if (! is_array($entities)) {
            $entities = [$entities];
        }

        foreach (array_chunk($entities, 500) as $chunk) {
            $searchableEntities = [];

            foreach ($chunk as $entity) {
                $className = ClassUtils::getClass($entity);
                $this->assertIsSearchable($className);

                $searchableEntities[] = new SearchableEntity(
                    $this->getFullIndexName($className),
                    $entity,
                    $objectManager->getClassMetadata($className),
                    $this->normalizer
                );
            }

            $this->engine->delete($searchableEntities);
        }

    }

    public function clear($className)
    {
        $this->assertIsSearchable($className);

        $this->engine->clear($this->getFullIndexName($className));
    }

    public function delete($className)
    {
        $this->assertIsSearchable($className);

        $this->engine->delete($this->getFullIndexName($className));
    }

    public function search($query, $className, ObjectManager $objectManager, $page = 1, $nbResults = null, array $parameters = [])
    {
        $this->assertIsSearchable($className);

        if (!is_int($nbResults)) {
            $nbResults = $this->nbResults;
        }

        $repo = $objectManager->getRepository($className);
        $ids = $this->engine->searchIds($query, $this->getFullIndexName($className), $page, $nbResults, $parameters);

        $results = [];
        foreach ($ids as $id) {
            $results[] = $repo->findOneBy(['id' => $id]);
        }

        return $results;
    }

    public function rawSearch($query, $className, $page = 1, $nbResults = null, array $parameters = [])
    {
        $this->assertIsSearchable($className);

        if (!is_int($nbResults)) {
            $nbResults = $this->nbResults;
        }

        return $this->engine->search($query, $this->getFullIndexName($className), $page,  $nbResults, $parameters);
    }

    public function count($query, $className, array $parameters = [])
    {
        $this->assertIsSearchable($className);

        return $this->engine->count($query, $this->getFullIndexName($className));
    }

    private function setClassToIndexMapping()
    {
        $mapping = [];
        foreach ($this->indexConfiguration as $indexName => $indexDetails) {
            $mapping[$indexDetails['class']] = $indexName;
        }

        $this->classToIndexMapping = $mapping;
    }

    private function setSearchableEntities()
    {
        $searchable = [];

        foreach ($this->indexConfiguration as $name => $index) {
            $searchable[] = $index['class'];
        }

        $this->searchableEntities = array_unique($searchable);
    }

    public function getFullIndexName($className)
    {
        return $this->prefix.$this->classToIndexMapping[$className];
    }

    private function assertIsSearchable($className)
    {
        if (!$this->isSearchable($className)) {
            throw new Exception('Class '.$className.' is not searchable.');
        }
    }
}
