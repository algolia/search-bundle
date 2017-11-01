<?php

namespace Algolia\SearchBundle\Encoder;


use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class SearchableArrayNormalizer implements NormalizerInterface
{
    protected $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    public function normalize($object, $format = null, array $context = array())
    {
        if ($object instanceof NormalizableInterface) {
            return $object->normalize($this, $format, $context);
        }

        $normalized = $this->toArray($object, $context);

        return $normalized;
    }

    /**
     * Checks whether the given class is supported for normalization by this normalizer.
     *
     * @param mixed $data Data to normalize
     * @param string $format The format being (de-)serialized from or into
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        return 'searchableArray' === $format;
    }

    public function toArray($object, $context)
    {
        $normalized = array();

        foreach ($context['fieldsMapping'] as $name => $meta) {
            // TODO: Use field name or column name?
            $normalized[$name] = $this->getNormalizedValue($object, $name);
        }

        return $normalized;
    }

    protected function getNormalizedValue($object, $name)
    {
        $value = $this->propertyAccessor->getValue($object, $name);

        switch (gettype($value)) {
            case 'unknown type':
            case 'resource':
                throw new \Exception('Cannot convert object');
            case 'object':
                if ($value instanceof \DateTimeInterface) {
                    return $value->getTimestamp();
                }

                // TODO: Do something clever and extensible
                return (array) $value;
            default:
                return $value;
        }
    }
}
