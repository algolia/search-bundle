<?php

namespace Algolia\SearchBundle;

use Doctrine\ORM\Mapping\ClassMetadata;
use JMS\Serializer\ArrayTransformerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SearchableEntity implements SearchableEntityInterface
{
    protected $indexName;
    protected $entity;

    /**
     * @var ClassMetadata
     */
    protected $entityMetadata;

    /**
     * @var string[] List of groups to use during serialization
     */
    protected $serializerGroups = [Searchable::NORMALIZATION_GROUP];

    /**
     * @var bool
     */
    protected $useSerializerGroups = false;

    private $id;
    private $normalizer;

    public function __construct($indexName, $entity, $entityMetadata, $normalizer, array $extra = [])
    {
        $this->indexName           = $indexName;
        $this->entity              = $entity;
        $this->entityMetadata      = $entityMetadata;
        $this->normalizer          = $normalizer;

        if (isset($extra['useSerializerGroup']) && $extra['useSerializerGroup']) {
            $this->useSerializerGroups = true;
        }

        if (isset($extra['serializerGroups']) && \is_array($extra['serializerGroups'])) {
            $this->serializerGroups = $extra['serializerGroups'];
        }

        $this->setId();
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    public function getSearchableArray()
    {
        $context = [
            'rootEntity'    => $this->entityMetadata->name,
            'fieldsMapping' => $this->entityMetadata->fieldMappings,
        ];

        if ($this->useSerializerGroups) {
            $context['groups'] = $this->serializerGroups;
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
