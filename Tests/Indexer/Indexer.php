<?php
namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Indexer;

class Indexer extends \Algolia\AlgoliaSearchSymfonyDoctrineBundle\Indexer\Indexer
{
    public $creations = array();
    public $updates = array();
    public $deletions = array();
    public $isolated_from_algolia;

    public function isolateFromAlgolia($isolated_from_algolia = true)
    {
        $this->isolated_from_algolia = $isolated_from_algolia;

        return $this;
    }

    public function performBatchCreations(array $creations)
    {
        $this->creations = $creations;

        if (!$this->isolated_from_algolia) {
            return parent::performBatchCreations($creations);
        }
    }

    public function performBatchUpdates(array $updates)
    {
        $this->updates = $updates;

        if (!$this->isolated_from_algolia) {
            return parent::performBatchUpdates($updates);
        }
    }

    public function performBatchDeletions(array $deletions)
    {
        $this->deletions = $deletions;
        if (!$this->isolated_from_algolia) {
            return parent::performBatchDeletions($deletions);
        }
    }

    public function reset()
    {
        $this->creations = array();
        $this->updates = array();
        $this->deletions = array();
    }

    protected function newInstance()
    {
        $indexer = parent::newInstance();
        $indexer->isolateFromAlgolia($this->isolated_from_algolia);

        return $indexer;
    }

    public function setApiSettings($application_id, $api_key)
    {
        global $kernel;

        try {
            if ($kernel->getContainer()->getParameter('algolia.get_secrets_from_travis')) {
                $application_id = getenv('ALGOLIA_APPLICATION_ID');
                $api_key = getenv('ALGOLIA_API_KEY');
            }
            
        } catch (\Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException $e) {
            // ignore this, it means we're just not running on Travis
        }

        parent::setApiSettings($application_id, $api_key);

        return $this;
    }
}
