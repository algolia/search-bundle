<?php

namespace Algolia\SearchBundle;

use Doctrine\ORM\Mapping\ClassMetadata;
use JMS\Serializer\ArrayTransformerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class SearchableEntity
{
    /**
     * @var string
     */
    private $indexName;

    /**
     * @var object
     */
    private $entity;

    /**
     * @var ClassMetadata
     */
    private $entityMetadata;

    /**
     * @var bool
     */
    private $useSerializerGroups;

    /**
     * @var int|string
     */
    private $id;

    /**
     * @var object
     */
    private $normalizer;

    /**
     * @param string                               $indexName
     * @param object                               $entity
     * @param ClassMetadata                        $entityMetadata
     * @param object                               $normalizer
     * @param array<string, int|string|array|bool> $extra
     */
    public function __construct($indexName, $entity, $entityMetadata, $normalizer, array $extra = [])
    {
        $this->indexName           = $indexName;
        $this->entity              = $entity;
        $this->entityMetadata      = $entityMetadata;
        $this->normalizer          = $normalizer;
        $this->useSerializerGroups = isset($extra['useSerializerGroup']) && $extra['useSerializerGroup'];

        $this->setId();
    }

    /**
     * @return string
     */
    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * @return array<string, int|string|array>
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
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

    /**
     * @return void
     */
    private function setId()
    {
        $ids = $this->entityMetadata->getIdentifierValues($this->entity);

        if (count($ids) === 0) {
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

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }
}
