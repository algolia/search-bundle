<?php

namespace Algolia\AlgoliaSearchBundle\Mapping\Loader;

use Symfony\Component\Yaml\Yaml;

class YamlLoader extends AbstractFileLoader
{
    protected function loadMetadataFromFile(\ReflectionClass $class, $file)
    {
        $config = Yaml::parse(file_get_contents($file));

        if ( ! isset($config[$name = $class->name])) {
            throw new \RuntimeException(sprintf('Expected metadata for class %s to be defined in %s.', $class->name, $file));
        }

        return null;
    }

    protected function getExtension()
    {
        return 'yml';
    }
}