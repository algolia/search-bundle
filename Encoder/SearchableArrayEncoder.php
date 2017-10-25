<?php

namespace Algolia\SearchBundle\Encoder;


use Symfony\Component\Serializer\Encoder\EncoderInterface;

class SearchableArrayEncoder implements EncoderInterface//, DecoderInterface
{
    public function encode($data, $format, array $context = array())
    {
        global $kernel;
        $classMetadata = $kernel->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getClassMetadata($context['entity']);

        foreach ($data as $fieldName => $fieldValue) {
            // Remove relationships
            if (! array_key_exists($fieldName, $classMetadata->columnNames)) {
                unset($data[$fieldName]);
                continue;
            }

            $mapping = $classMetadata->fieldMappings[$fieldName];
            switch ($mapping['type']) {
                case 'datetime':
                    $data[$fieldName] = $fieldValue['timestamp'];
                    break;
                default:
                    break;
            }

        }

        return $data;
    }

    public function supportsEncoding($format)
    {
        return 'searchableArray' === $format;
    }
}
