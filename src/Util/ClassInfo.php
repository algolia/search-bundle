<?php

namespace Algolia\SearchBundle\Util;

/**
 * Retrieves information about a class.
 *
 * @internal
 */
final class ClassInfo
{
    /**
     * Get class name of the given object.
     *
     * @param object $object
     *
     * @return string
     */
    public static function getClass($object)
    {
        return self::getRealClassName(get_class($object));
    }

    /**
     * Get the real class name of a class name that could be a proxy.
     *
     * @param string $className
     *
     * @return string
     */
    public static function getRealClassName($className)
    {
        // Define variable for static analysis
        $positionPm = false;
        // __CG__: Doctrine Common Marker for Proxy (ODM < 2.0 and ORM < 3.0)
        // __PM__: Ocramius Proxy Manager (ODM >= 2.0)
        if ((false === $positionCg = strrpos($className, '\\__CG__\\')) &&
            (false === $positionPm = strrpos($className, '\\__PM__\\'))) {
            return $className;
        }

        if (false !== $positionCg) {
            return substr($className, $positionCg + 8);
        }

        $className = ltrim($className, '\\');

        return substr(
            $className,
            8 + $positionPm,
            strrpos($className, '\\') - ($positionPm + 8)
        );
    }

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
