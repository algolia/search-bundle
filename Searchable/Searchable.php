<?php

namespace Algolia\SearchBundle\Searchable;

use Algolia\SearchBundle\Encoder\EntityNormalizer;
use Algolia\SearchBundle\Encoder\SearchableArrayEncoder;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Serializer;

class Searchable implements SearchableInterface
{
    protected $entity;

    protected $entityMetaData;

    public function __construct($entity, $entityMetaData)
    {
        $this->entity = $entity;
        $this->entityMetaData = $entityMetaData;
    }

    public function getIndexName()
    {
        // TODO: Use actual env var
        return 'ENV_' . $this->entityMetaData->table['name'];
    }

    public function getRecord()
    {
        return ['my record' => 'should be here'];
    }

    public function getObjectID()
    {
        $ids = $this->entityMetaData->getIdentifierValues($this->entity);

        if (empty($ids)) {
            throw new Exception('Entity has no primary key');
        }

        if (1 == count($ids)) {
            return reset($ids);
        }

        $objectID = '';
        foreach ($ids as $key => $value) {
            $objectID .= $key . '-' . $value . '__';
        }

        return rtrim($objectID, '_');
    }
}
