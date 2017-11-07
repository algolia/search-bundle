<?php

namespace Algolia\SearchBundle\Searchable;


use Algolia\SearchBundle\Engine\EngineInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Config\Definition\Exception\Exception;

class IndexManager implements IndexingManagerInterface, SearchManagerInterface
{
    protected $engine;

    protected $indexConfiguration;

    protected $prefix;

    private $searchableEntities;

    private $classToIndexMapping;

    public function __construct(EngineInterface $engine, array $indexConfiguration, $prefix)
    {
        $this->engine = $engine;
        $this->indexConfiguration = $indexConfiguration;
        $this->prefix = $prefix;

        $this->setSearchableEntities();
        $this->setClassToIndexMapping();
    }

    public function isSearchable($className)
    {
        if (is_object($className)) {
            $className = get_class($className);
        }

        return in_array($className, $this->searchableEntities);
    }

    public function index($entity, ObjectManager $objectManager)
    {
        $className = get_class($entity);

        $this->assertIsSearchable($className);

        foreach ($this->classToIndexMapping[$className] as $indexName) {

            $this->engine->update(new SearchableEntity(
                $this->prefix.$indexName,
                $entity,
                $objectManager->getClassMetadata($className),
                $this->indexConfiguration[$indexName]['normalizers']
            ));
        }
    }

    public function clear($indexName)
    {
        $this->engine->clear($this->prefix.$indexName);
    }

    public function delete($entity, ObjectManager $objectManager)
    {
        $className = get_class($entity);

        $this->assertIsSearchable($className);

        foreach ($this->classToIndexMapping[$className] as $indexName) {

            $this->engine->delete(new SearchableEntity(
                $this->prefix.$indexName,
                $entity,
                $objectManager->getClassMetadata($className)
            ));
        }
    }

    public function search($query, $className, ObjectManager $objectManager, $nbResults = 20, $page = 0, array $parameters = [])
    {
        $this->assertIsSearchable($className);

        $repo = $objectManager->getRepository($className);
        $ids = $this->engine->searchIds($query, $this->getFullIndexName($className), $nbResults, $page, $parameters);

        $results = $repo->findBy(['id' => $ids]);

        return $results;
    }

    public function rawSearch($query, $className, $nbResults = 20, $page = 0, array $parameters = [])
    {
        $this->assertIsSearchable($className);

        return $this->engine->search($query, $this->getFullIndexName($className), $nbResults = 20, $page = 0, $parameters);
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

    private function getFullIndexName($className)
    {
        return $this->prefix.$this->classToIndexMapping[$className];
    }

    private function assertIsSearchable($className)
    {
        if (! $this->isSearchable($className)) {
            throw new Exception('Class '.$className.' is not searchable.');
        }
    }
}
