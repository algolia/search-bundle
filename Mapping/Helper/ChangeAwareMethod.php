<?php

namespace Algolia\AlgoliaSearchBundle\Mapping\Helper;

class ChangeAwareMethod
{
    private $name;

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
	 * This function violates all OOP design practices
	 * by setting private properties of an external object.
	 *
	 * Well done, PHP :) http://php.net/manual/fr/closure.bind.php
	 *
	 * But we need this, as we can't assume there are setters on all fields.
	 * And this is far more efficient than using a ReflectionClass:
	 * http://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
	 */
    private function fillWith($entity, array $data)
    {
        $privateSetter = \Closure::bind(function () use ($data) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }, $entity, $entity);

        $privateSetter();

        return $entity;
    }

    public function diff($entity, array $changeSet)
    {
        $oldValues = [];
        foreach ($changeSet as $field => $oldNew) {
            $oldValues[$field] = $oldNew[0];
        }

        $oldValue = $this->evaluateWith($entity, $oldValues);
        $newValue = $this->evaluate($entity);

        return array($newValue, $oldValue);
    }

    public function evaluateWith($entity, array $data)
    {
        $oldEntity = $this->fillWith(clone $entity, $data);

        return $this->evaluate($oldEntity);
    }

    public function evaluate($entity)
    {
        return call_user_func(array($entity, $this->getName()));
    }
}
