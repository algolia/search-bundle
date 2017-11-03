<?php

namespace Algolia\SearchBundle\Searchable;

use Algolia\SearchBundle\Normalizer\SearchableArrayNormalizer;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Serializer;

class SearchableEntity implements SearchableEntityInterface
{
    protected $indexName;
    protected $entity;
    protected $entityMetadata;
    protected $normalizers;

    public function __construct($indexName, $entity, $entityMetadata, array $normalizers = [])
    {
        $this->indexName = $indexName;
        $this->entity = $entity;
        $this->entityMetadata = $entityMetadata;
        $this->normalizers = $normalizer ?? [new SearchableArrayNormalizer()];
    }

    public function getIndexName()
    {
        return getenv('ALGOLIA_PREFIX').$this->indexName;
    }

    public function getSearchableArray()
    {
        $serializer = new Serializer($this->normalizers);

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
