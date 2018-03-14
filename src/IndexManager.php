<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\Engine\EngineInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class IndexManager implements IndexManagerInterface
{
    protected $engine;
    protected $configuration;
    protected $useSerializerGroups;

    private $searchableEntities;
    private $classToIndexMapping;
    private $classToSerializerGroupMapping;
    private $normalizer;

    public function __construct(NormalizerInterface $normalizer, EngineInterface $engine, array $configuration)
    {
        $this->normalizer          = $normalizer;
        $this->engine              = $engine;
        $this->configuration       = $configuration;

        $this->setSearchableEntities();
        $this->setClassToIndexMapping();
        $this->setClassToSerializerGroupMapping();
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

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function index($entities, ObjectManager $objectManager)
    {
        if (! is_array($entities)) {
            $entities = [$entities];
        }

        $batch = [];
        foreach (array_chunk($entities, $this->configuration['batchSize']) as $chunk) {
            $searchableEntities = [];

            foreach ($chunk as $entity) {
                $className = ClassUtils::getClass($entity);
                $this->assertIsSearchable($className);

                $searchableEntities[] = new SearchableEntity(
                    $this->getFullIndexName($className),
                    $entity,
                    $objectManager->getClassMetadata($className),
                    $this->normalizer,
                    ['useSerializerGroup' => $this->canUseSerializerGroup($className)]
                );
            }

            $batch[] = $this->engine->update($searchableEntities);
        }

        return $this->formatBatchResponse($batch);
    }

    public function remove($entities, ObjectManager $objectManager)
    {
        if (! is_array($entities)) {
            $entities = [$entities];
        }

        $batch = [];
        foreach (array_chunk($entities, $this->configuration['batchSize']) as $chunk) {
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

            $batch[] = $this->engine->remove($searchableEntities);
        }

        return $this->formatBatchResponse($batch);
    }

    public function clear($className)
    {
        $this->assertIsSearchable($className);

        return $this->engine->clear($this->getFullIndexName($className));
    }

    public function delete($className)
    {
        $this->assertIsSearchable($className);

        return $this->engine->delete($this->getFullIndexName($className));
    }

    public function search($query, $className, ObjectManager $objectManager, $page = 1, $nbResults = null, array $parameters = [])
    {
        $this->assertIsSearchable($className);

        if (!is_int($nbResults)) {
            $nbResults = $this->configuration['nbResults'];
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
            $nbResults = $this->configuration['nbResults'];
        }

        return $this->engine->search($query, $this->getFullIndexName($className), $page,  $nbResults, $parameters);
    }

    public function count($query, $className, array $parameters = [])
    {
        $this->assertIsSearchable($className);

        return $this->engine->count($query, $this->getFullIndexName($className));
    }

    private function canUseSerializerGroup($className)
    {
        return $this->classToSerializerGroupMapping[$className];
    }

    private function setClassToIndexMapping()
    {
        $mapping = [];
        foreach ($this->configuration['indices'] as $indexName => $indexDetails) {
            $mapping[$indexDetails['class']] = $indexName;
        }

        $this->classToIndexMapping = $mapping;
    }

    private function setSearchableEntities()
    {
        $searchable = [];

        foreach ($this->configuration['indices'] as $name => $index) {
            $searchable[] = $index['class'];
        }

        $this->searchableEntities = array_unique($searchable);
    }

    public function getFullIndexName($className)
    {
        return $this->configuration['prefix'].$this->classToIndexMapping[$className];
    }

    private function assertIsSearchable($className)
    {
        if (!$this->isSearchable($className)) {
            throw new Exception('Class '.$className.' is not searchable.');
        }
    }

    private function setClassToSerializerGroupMapping()
    {
        $mapping = [];
        foreach ($this->configuration['indices'] as $indexDetails) {
            $mapping[$indexDetails['class']] = $indexDetails['enable_serializer_groups'];
        }

        $this->classToSerializerGroupMapping = $mapping;
    }

    private function formatBatchResponse(array $batch)
    {
        $formattedResponse = [];
        foreach ($batch as $response) {
            foreach ($response as $fullIndexName => $count) {
                $indexName = $this->removePrefixFromIndexName($fullIndexName);

                if (!isset($formattedResponse[$indexName])) {
                    $formattedResponse[$indexName] = 0;
                }

                $formattedResponse[$indexName] += $count;
            }
        }

        return $formattedResponse;
    }

    private function removePrefixFromIndexName($indexName)
    {
        return preg_replace('/^'.preg_quote($this->configuration['prefix'], '/').'/', '', $indexName);
    }
}
