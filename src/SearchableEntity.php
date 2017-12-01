<?php

namespace Algolia\SearchBundle;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SearchableEntity implements SearchableEntityInterface
{
    protected $indexName;
    protected $entity;
    protected $entityMetadata;

    private $id;
    private $normalizer;

    public function __construct($indexName, $entity, $entityMetadata, NormalizerInterface $normalizer)
    {
        $this->indexName      = $indexName;
        $this->entity         = $entity;
        $this->entityMetadata = $entityMetadata;
        $this->normalizer     = $normalizer;

        $this->setId();
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    public function getSearchableArray()
    {
        return $this->normalizer->normalize($this->entity, SearchFormat::NORMALIZATION_FORMAT, [
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
