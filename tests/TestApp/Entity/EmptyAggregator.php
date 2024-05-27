<?php

namespace Algolia\SearchBundle\TestApp\Entity;

use Algolia\SearchBundle\Entity\Aggregator;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class EmptyAggregator extends Aggregator
{
}
