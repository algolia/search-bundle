<?php

namespace Algolia\SearchBundle\Searchable;


use Algolia\SearchBundle\Engine\IndexingEngineInterface;
use Doctrine\Common\Persistence\ObjectManager;

class IndexManager implements IndexManagerInterface
{
    protected $engine;

    protected $indexConfiguration;

    protected $prefix;

    private $searchableEntities;

    private $classToIndexMapping;

    public function __construct(IndexingEngineInterface $engine, array $indexConfiguration, $prefix)
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

    public function getIndexConfiguration()
    {
        return $this->indexConfiguration;
    }

    public function getSearchableEntities()
    {
        return $this->searchableEntities;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function index($entity, ObjectManager $objectManager)
    {
        $className = get_class($entity);

        if (! $this->isSearchable($className)) {
            return;
        }

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

        if (! $this->isSearchable($className)) {
            return;
        }

        foreach ($this->classToIndexMapping[$className] as $indexName) {

            $this->engine->delete(new SearchableEntity(
                $this->prefix.$indexName,
                $entity,
                $objectManager->getClassMetadata($className)
            ));
        }
    }

    private function setClassToIndexMapping()
    {
        $mapping = [];
        foreach ($this->indexConfiguration as $indexName => $indexDetails) {
            if (! isset($mapping[$indexDetails['class']])) {
                $mapping[$indexDetails['class']] = [];
            }

            $mapping[$indexDetails['class']][] = $indexName;
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
}
