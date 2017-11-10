<?php

namespace Algolia\SearchBundle\Searchable;

use Algolia\SearchBundle\Normalizer\SearchableArrayNormalizer;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Serializer;

class SearchableEntity implements SearchableEntityInterface
{
    private $id;
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

        $this->setId();
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    public function getSearchableArray()
    {
        $serializer = new Serializer($this->normalizers);

        return $serializer->normalize($this->entity, 'searchableArray', [
            'fieldsMapping' => $this->entityMetadata->fieldMappings,
        ]);
    }

    private function setId()
    {
        $ids = $this->entityMetadata->getIdentifierValues($this->entity);

        if (empty($ids)) {
            throw new Exception('Entity has no primary key');
        }

        if (1 == count($ids)) {
            $this->id = reset($ids);
        } else {
            $objectID = '';
            foreach ($ids as $key => $value) {
                $objectID .= $key . '-' . $value . '__';
            }

            $this->id = rtrim($objectID, '_');
        }

    }

    public function getId()
    {
        return $this->id;
    }
}
