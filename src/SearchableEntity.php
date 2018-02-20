<?php

namespace Algolia\SearchBundle;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SearchableEntity implements SearchableEntityInterface
{
    protected $indexName;
    protected $entity;
    protected $entityMetadata;
    protected $useSerializerGroups;
    protected $objectIDGetter;

    private $id;
    private $normalizer;

    public function __construct($indexName, $entity, $entityMetadata, NormalizerInterface $normalizer, array $extra = [])
    {
        $this->indexName           = $indexName;
        $this->entity              = $entity;
        $this->entityMetadata      = $entityMetadata;
        $this->normalizer          = $normalizer;
        $this->useSerializerGroups = isset($extra['useSerializerGroup']) && $extra['useSerializerGroup'];
        $this->objectIDGetter      = isset($extra['objectID']) ? $extra['objectID'] : null;

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

        return $this->normalizer->normalize($this->entity, Searchable::NORMALIZATION_FORMAT, $context);
    }

    private function setId()
    {
        if ($this->objectIDGetter !== null && method_exists($this->entity, $this->objectIDGetter)) {
            $this->id = $this->entity->{$this->objectIDGetter}();

            if ($this->id === null) {
                throw new Exception(sprintf('Entity %s has no valid key', get_class($this->entity)));
            }

            return;
        }

        $ids = $this->entityMetadata->getIdentifierValues($this->entity);

        if (empty($ids)) {
            throw new Exception(sprintf('Entity %s has no valid primary key', get_class($this->entity)));
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
