<?php

namespace Algolia\AlgoliaSearchBundle\Tests\Debug;

use Algolia\AlgoliaSearchBundle\Debug\DebugClient;

class DebugClientTest extends \PHPUnit_Framework_TestCase
{
    public function testDoRequestWithDisabledRequestsReturnsEmptyArray()
    {
        $testInstance = new DebugClient('app_id', 'api_key');
        $testInstance->disableRequests(true);

        $response = $testInstance->doRequest('s', 's', 's', 's', [], [], 13, 37);

        self::assertInternalType('array', $response);
        self::assertEmpty($response);
    }

    public function testDoRequestWithDisabledRequestsReturnsPushedResponse()
    {
        $response = ['success' => true];

        $testInstance = new DebugClient('app_id', 'api_key');
        $testInstance->disableRequests(true);
        $testInstance->pushResponse($response);

        self::assertSame($response, $testInstance->doRequest('s', 's', 's', 's', [], [], 13, 37));
    }
}
