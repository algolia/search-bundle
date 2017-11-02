<?php

namespace Algolia\SearchBundle\Searchable;


use Algolia\SearchBundle\Engine\IndexingEngineInterface;
use Doctrine\Common\Persistence\ObjectManager;

class IndexManager implements IndexManagerInterface
{
    protected $engine;

    protected $indices;

    protected $prefix;

    private $searchableEntities;

    public function __construct(IndexingEngineInterface $engine, array $indices, $prefix)
    {
        $this->engine = $engine;
        $this->indices = $indices;
        $this->prefix = $prefix;

        $this->searchableEntities = array_column($indices, 'class');
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

        if (! $this->isSearchable($className)) {
            return;
        }

        foreach ($this->indices as $index) {
            if ($index['class'] !== $className) {
                continue;
            }

            $this->engine->update(new Searchable(
                $this->prefix.$index['name'],
                $entity,
                $objectManager->getClassMetadata($className),
                $index['normalizers']
            ));
        }

    }
}
