<?php

namespace Algolia\SearchBundle;

interface AggregatorInterface
{
    /**
     * Returns an entity id from the provided object id.
     *
     * @param  string $objectID
     * @return object
     */
    public static function getEntityIdFromObjectID($objectID);

    /**
     * Returns an entity class name from the provided object id.
     *
     * @param  string $objectID
     * @return object
     */
    public static function getEntityClassFromObjectID($objectID);

    /**
     * Returns the entities class names that should be aggregated.
     *
     * @return string[]
     */
    public static function getEntities();

    /**
     * Returns the unique Algolia object id. Should
     * contain information about the entity class and
     * about the entity id.
     *
     * Example: Post_1 or Comment_2.
     *
     * @return string
     */
    public function getObjectID();
}
