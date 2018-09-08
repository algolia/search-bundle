<?php

namespace Algolia\SearchBundle;


use Algolia\SearchBundle\Exception\EntityNotFoundInObjectID;
use Algolia\SearchBundle\Exception\InvalidEntityForAggregator;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use ReflectionClass;

/**
 * @ORM\MappedSuperclass
 */
abstract class Aggregator implements AggregatorInterface, NormalizableInterface
{
    /**
     * Holds an doctrine {@ORM\Entity} object.
     *
     * @var object
     */
    protected $entity;

    /**
     * Holds the entity identifier values.
     *
     * Keep in mind that entity aggregators do not support
     * entities with more than one primary key.
     *
     * @var array
     */
    protected $entityIdentifierValues;

    /**
     * Holds an array of entities where the short names
     * got already resolved. Acts as a cache strategy.
     *
     * @var string[]
     */
    protected static $resolvedEntitiesShortNames = [];

    /**
     * Holds the ObjectID.
     *
     * Typically also contains information concerning the
     * entity class name, and concerning the entity id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="string")
     */
    protected $objectID;

    /**
     * Aggregator constructor.
     *
     * @param object $entity
     * @param array $entityIdentifierValues
     */
    public function __construct($entity, array $entityIdentifierValues)
    {
        $this->entity = $entity;

        if (count($entityIdentifierValues) > 1) {
            throw new InvalidEntityForAggregator("Aggregators don't support more than one primary key");
        }

        $this->entityIdentifierValues = $entityIdentifierValues;
        $this->objectID = $this->getObjectID();
    }

    /**
     * {@inheritdoc}
     */
    public static function getEntities()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getEntityIdFromObjectID($objectID)
    {
        $partsOfObjectID = explode('_', $objectID);

        return \end($partsOfObjectID);
    }

    /**
     * {@inheritdoc}
     */
    public static function getEntityClassFromObjectID($objectID)
    {
        $partsOfObjectID = explode('_', $objectID);

        $type = \current($partsOfObjectID);

        foreach (static::getEntities() as $entityClassName) {
            if (static::getClassShortName($entityClassName) === $type) {
                return $entityClassName;
            }
        }

        throw new EntityNotFoundInObjectID("Entity from ObjectID {$objectID} couldn't be retrieved");
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectID()
    {
        $id = reset($this->entityIdentifierValues);

        $entityClassName = get_class($this->entity);

        return static::getClassShortName($entityClassName) . '_' . $id;
    }


    /**
     * {@inheritdoc}
     */
    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = array())
    {
        return array_merge(
            ['objectID' => $this->getObjectID()],
            $normalizer->normalize($this->entity)
        );
    }

    /**
     * Resolves the class short name from the provided entity class name.
     *
     * @param  string $entityClassName
     * @return string
     *
     * @throws \ReflectionException If provided entity class name do not exist.
     */
    protected static function getClassShortName($entityClassName)
    {
        if (! array_key_exists($entityClassName, static::$resolvedEntitiesShortNames)) {
            static::$resolvedEntitiesShortNames[$entityClassName] = (new ReflectionClass($entityClassName))->getShortName();
        }

        return static::$resolvedEntitiesShortNames[$entityClassName];
    }
}
