<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\Entity\Post;
use AlgoliaSearch\AlgoliaException;

class AlgoliaIndexManagerTest extends BaseTest
{
    /** @var \Algolia\SearchBundle\IndexManagerInterface */
    protected $indexManager;

    public function setUp()
    {
        parent::setUp();
        $this->indexManager = $this->get('search.index_manager');
    }

    public function testSetCredentials()
    {
        try {
            $this->indexManager->setCredentials('xxx', 'yyy')->count('', Post::class);
            $this->assertTrue(false, "Credentials couldn't be set in the AlgoliaIndexManager");
        } catch (AlgoliaException $e) {
            $this->assertContains('Invalid Application-ID or API key', $e->getMessage());
        }

        $this->indexManager->setCredentials(
            getenv('ALGOLIA_APP_ID'),
            getenv('ALGOLIA_API_KEY')
        );
    }
}
