<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch;

use Algolia\AlgoliaSearchBundle\Tests\BaseTest;
use Algolia\AlgoliaSearchBundle\Tests\Entity;

abstract class CallbacksTest extends BaseTest
{
    // Just to be sure we did not mess something up in our namespaces
    public function testOurEntitiesExist()
    {
        new Entity\Product();
        new Entity\Supplier();
    }

    public function testCreateUpdateDeleteCallbacksAreCalled()
    {
        $product = new Entity\Product();

        $product
        ->setName("Hello World");

        $this->assertEquals(null, $product->getTestProp('create_callback'));
        $this->persistAndFlush($product);
        $this->assertEquals('called', $product->getTestProp('create_callback'));

        $this->assertEquals(null, $product->getTestProp('update_callback'));
        $product->setName("Hello World 2014");
        $this->persistAndFlush($product);
        $this->assertEquals('called', $product->getTestProp('update_callback'));

        $this->assertEquals(null, $product->getTestProp('delete_callback'));
        $this->removeAndFlush($product);
        $this->assertEquals('called', $product->getTestProp('delete_callback'));
    }

    public function testCreateUpdateDeleteCallbacksAreCalledInRelationsToo()
    {
        $supplier = new Entity\Supplier();
        $supplier->setName("Algolia");

        $product = new Entity\Product();
        $product
        ->setName('Search As a Service');

        $this->assertEquals(null, $product->getTestProp('create_callback'));
        $supplier->addProduct($product);
        $this->persistAndFlush($supplier);
        $this->assertEquals('called', $product->getTestProp('create_callback'));

        $this->assertEquals(null, $product->getTestProp('update_callback'));
        $product->setPrice(29.99);
        $this->persistAndFlush($supplier);
        $this->assertEquals('called', $product->getTestProp('update_callback'));

        $this->assertEquals(null, $product->getTestProp('delete_callback'));
        $this->removeAndFlush($supplier);
        $this->assertEquals('called', $product->getTestProp('delete_callback'));
    }
}
