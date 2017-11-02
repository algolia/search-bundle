<?php

namespace Algolia\SearchBundle\Searchable;

use Algolia\SearchBundle\Encoder\SearchableArrayNormalizer;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class Searchable implements SearchableInterface
{
    protected $indexName;
    protected $entity;
    protected $entityMetadata;
    protected $normalizer;

    public function __construct($indexName, $entity, $entityMetadata, NormalizerInterface $normalizer = null)
    {
        $this->indexName = $indexName;
        $this->entity = $entity;
        $this->entityMetadata = $entityMetadata;
        $this->normalizer = $normalizer ?? new SearchableArrayNormalizer();
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    public function getSearchableArray()
    {
        $serializer = new Serializer([$this->normalizer]);

        return $serializer->normalize($this->entity, 'searchableArray', [
            'fieldsMapping' => $this->entityMetadata->fieldMappings,
        ]);
    }

    public function getId()
    {
        $ids = $this->entityMetadata->getIdentifierValues($this->entity);

        if (empty($ids)) {
            throw new Exception('Entity has no primary key');
        }

        if (1 == count($ids)) {
            return reset($ids);
        }

        $objectID = '';
        foreach ($ids as $key => $value) {
            $objectID .= $key . '-' . $value . '__';
        }

        return rtrim($objectID, '_');
    }
}
