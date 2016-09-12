<?php

namespace Algolia\AlgoliaSearchBundle\Mapping\Loader;

use Algolia\AlgoliaSearchBundle\Mapping\Description;
use Algolia\AlgoliaSearchBundle\Mapping\Index;
use Algolia\AlgoliaSearchBundle\Mapping\IndexIf;
use Algolia\AlgoliaSearchBundle\Mapping\Method;
use Algolia\AlgoliaSearchBundle\Mapping\Property;
use Symfony\Component\Yaml\Yaml;

class YamlLoader extends AbstractFileLoader
{
    protected function loadMetadataFromFile(\ReflectionClass $class, $file)
    {
        $config = Yaml::parse(file_get_contents($file));

        if (!isset($config[$name = $class->name])) {
            throw new \RuntimeException(sprintf('Expected metadata for class %s to be defined in %s.', $class->name, $file));
        }

        $config = $config[$name];
        $className = $this->removeProxy($name);
        $description = new Description($class);

        $this->extractClassMetadatas($config, $className, $description);

        $this->extractPropertyMetadata($class, $className, $config, $description);

        $this->extractMethodMetadata($class, $className, $config, $description);

        return $description;
    }

    /**
     * @param $config
     * @param $className
     * @param $description
     */
    protected function extractClassMetadatas($config, $className, Description $description)
    {
        $classMetadata = [];

        $classMetadata['perEnvironment'] = true;
        if (isset($config['perEnvironment'])) {
            $classMetadata['perEnvironment'] = $config['perEnvironment'];
        }

        $classMetadata['autoIndex'] = true;
        if (isset($config['autoIndex'])) {
            $classMetadata['autoIndex'] = $config['autoIndex'];
        }

        if (isset($config['algoliaName'])) {
            $classMetadata['algoliaName'] = $config['algoliaName'];
        }

        foreach (Index::$algoliaSettingsProps as $field) {
            if (isset($config[$field])) {
                $classMetadata[$field] = $config[$field];
            }
        }

        $index = new Index();
        $index->setAlgoliaNameFromClass($className);
        $index->updateSettingsFromArray($classMetadata);
        $description->setIndex($index);
    }

    /**
     * @param \ReflectionClass $class
     * @param $className
     * @param $config
     * @param $description
     */
    protected function extractPropertyMetadata(\ReflectionClass $class, $className, $config, Description $description)
    {
        foreach ($class->getProperties() as $property) {
            if ($className !== $property->class) {
                continue;
            }

            $propertyName = $property->getName();

            if (isset($config['properties'][$propertyName])) {
                $propertyConfig = $config['properties'][$propertyName];

                if (!isset($propertyConfig['type']) && !is_scalar($propertyConfig['type'])) {
                    throw new \RuntimeException('The "type" attribute must be set for each property.');
                }

                if ((string) $propertyConfig['type'] === 'attribute') {
                    $field = new Property();
                    $field->setName($property->getName());

                    if (isset($propertyConfig['algoliaName'])) {
                        $field->setAlgoliaName($propertyConfig['algoliaName']);
                    } else {
                        $field->setAlgoliaName($field->getName());
                    }

                    $description->addProperty($field);
                } elseif ((string) $propertyConfig['type'] === 'id') {
                    $description->addIdentifierAttributeName($propertyName);
                } else {
                    throw new \RuntimeException('The "type" attribute is wrongly configured.');
                }
            }
        }
    }

    /**
     * @param \ReflectionClass $class
     * @param $className
     * @param $config
     * @param $description
     */
    protected function extractMethodMetadata(\ReflectionClass $class, $className, $config, Description $description)
    {
        foreach ($class->getMethods() as $method) {
            if ($className !== $method->class) {
                continue;
            }

            $methodName = $method->getName();

            if (isset($config['methods'][$methodName])) {
                $methodConfig = $config['methods'][$methodName];

                if (!isset($methodConfig['type']) && !is_scalar($methodConfig['type'])) {
                    throw new \RuntimeException('The "type" attribute must be set for each methods.');
                }

                if ((string) $methodConfig['type'] === 'attribute') {
                    $field = new Method();
                    $field->setName($methodName);

                    if (isset($methodConfig['algoliaName'])) {
                        $field->setAlgoliaName($methodConfig['algoliaName']);
                    } else {
                        $field->setAlgoliaName(lcfirst(preg_replace('/^get([A-Z])/', '$1', $field->getName())));
                    }

                    $description->addMethod($field);
                } elseif ((string) $methodConfig['type'] === 'indexIf') {
                    $indexIf = new IndexIf();
                    $indexIf->setName($methodName);

                    $description->addIndexIf($indexIf);
                } else {
                    throw new \RuntimeException('The "type" attribute is wrongly configured on.');
                }
            }
        }
    }

    private function removeProxy($class)
    {
        /* Avoid proxy class form symfony */
        return str_replace('Proxies\\__CG__\\', '', $class);
    }

    protected function getExtension()
    {
        return 'yml';
    }
}
