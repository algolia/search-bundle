<?php

namespace Algolia\SearchBundle\Model;

use Algolia\SearchBundle\Exception\EntityNotFoundInObjectID;
use Algolia\SearchBundle\Exception\InvalidEntityForAggregator;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Doctrine\Common\Util\ClassUtils;

abstract class Aggregator implements NormalizableInterface
{
    /**
     * Holds the ObjectID.
     *
     * Typically also contains information concerning the
     * entity class name, and concerning the entity id.
     *
     * @var string
     */
    protected $objectID;

    /**
     * Holds an doctrine {@ORM\Entity} object.
     *
     * @var object
     */
    protected $entity;

    /**
     * @param object             $entity
     * @param array<int, string> $entityIdentifierValues
     */
    public function __construct($entity, array $entityIdentifierValues)
    {
        $this->entity = $entity;

        if (count($entityIdentifierValues) > 1) {
            throw new InvalidEntityForAggregator("Aggregators don't support more than one primary key.");
        }

        $this->objectID = ClassUtils::getClass($this->entity) . '::' . reset($entityIdentifierValues);
    }

    /**
     * Returns the entities class names that should be aggregated.
     *
     * @return string[]
     */
    public static function getEntities()
    {
        return [];
    }

    /**
     * Returns an entity id from the provided object id.
     *
     * @param string $objectID
     *
     * @return string
     */
    public static function getEntityIdFromObjectID($objectID)
    {
        return explode('::', $objectID)[1];
    }

    /**
     * Returns an entity class name from the provided object id.
     *
     * @param string $objectID
     *
     * @return string
     *
     * @throws EntityNotFoundInObjectID
     */
    public static function getEntityClassFromObjectID($objectID)
    {
        $type = explode('::', $objectID)[0];

        if (in_array($type, static::getEntities(), true)) {
            return $type;
        }

        throw new EntityNotFoundInObjectID("Entity class from ObjectID {$objectID} not found.");
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = [])
    {
        return array_merge(['objectID' => $this->objectID], $normalizer->normalize($this->entity, $format, $context));
    }
}
