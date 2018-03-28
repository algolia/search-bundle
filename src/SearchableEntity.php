<?php

namespace Algolia\SearchBundle;

use JMS\Serializer\ArrayTransformerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SearchableEntity implements SearchableEntityInterface
{
    protected $indexName;
    protected $entity;
    protected $entityMetadata;
    protected $useSerializerGroups;

    private $id;
    private $normalizer;

    public function __construct($indexName, $entity, $entityMetadata, $normalizer, array $extra = [])
    {
        $this->indexName           = $indexName;
        $this->entity              = $entity;
        $this->entityMetadata      = $entityMetadata;
        $this->normalizer          = $normalizer;
        $this->useSerializerGroups = isset($extra['useSerializerGroup']) && $extra['useSerializerGroup'];

        $this->setId();
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    public function getSearchableArray()
    {
        $context = [
            'fieldsMapping' => $this->entityMetadata->fieldMappings,
        ];

        if ($this->useSerializerGroups) {
            $context['groups'] = [Searchable::NORMALIZATION_GROUP];
        }

        if ($this->normalizer instanceof NormalizerInterface) {
            return $this->normalizer->normalize($this->entity, Searchable::NORMALIZATION_FORMAT, $context);
        } elseif ($this->normalizer instanceof ArrayTransformerInterface) {
            return $this->normalizer->toArray($this->entity);
        }
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
