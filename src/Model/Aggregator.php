<?php

namespace Algolia\SearchBundle\Model;

use Algolia\SearchBundle\Exception\EntityNotFoundInObjectID;
use Algolia\SearchBundle\Exception\InvalidEntityForAggregator;
use Algolia\SearchBundle\Util\ClassInfo;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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

        $this->objectID = ClassInfo::getClass($this->entity) . '::' . reset($entityIdentifierValues);
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
     * Normalizes the object into an array of scalars|arrays.
     *
     * It is important to understand that the normalize() call should normalize
     * recursively all child objects of the implementor.
     *
     * @param NormalizerInterface $normalizer The normalizer is given so that you
     *                                        can use it to normalize objects contained within this object
     * @param string|null         $format     The format is optionally given to be able to normalize differently
     *                                        based on different output formats
     * @param array               $context    Options for normalizing this object
     */
    public function normalize(NormalizerInterface $normalizer, ?string $format = null, array $context = []): array|string|int|float|bool
    {
        return array_merge(['objectID' => $this->objectID], $normalizer->normalize($this->entity, $format, $context));
    }
}
