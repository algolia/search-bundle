<?php

namespace Algolia\AlgoliaSearchBundle\Debug;

use AlgoliaSearch\Client;
use Symfony\Component\Stopwatch\Stopwatch;

class DebugClient extends Client implements DebugClientInterface
{
    /**
     * @var array
     */
    private $transactions = [];

    /**
     * @var bool
     */
    private $disableRequests = false;

    /**
     * @var array
     */
    private $responseStack = [];

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @param bool $disable
     */
    public function disableRequests($disable = false)
    {
        $this->disableRequests = $disable;
    }

    /**
     * @param array $response
     */
    public function pushResponse(array $response)
    {
        $this->responseStack[] = $response;
    }

    /**
     * @param Stopwatch $stopwatch
     */
    public function setStopwatch(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * {@inheritdoc}
     */
    public function doRequest($context, $method, $host, $path, $params, $data, $connectTimeout, $readTimeout)
    {
        $transactionId = md5(microtime() . uniqid());

        $request = [
            'context' => $context,
            'method' => $method,
            'host' => $host,
            'path' => $path,
            'params' => $params,
            'data' => $data,
            'connect_timeout' => $connectTimeout,
            'read_timeout' => $readTimeout,
        ];

        if ($this->stopwatch) {
            $this->stopwatch->start('algolia_transaction');
        }
        $start = microtime(true);

        $response = [];

        if ($this->disableRequests) {
            if ($this->responseStack !== []) {
                $response = array_shift($this->responseStack);
            }
        } else {
            $response = parent::doRequest($context, $method, $host, $path, $params, $data, $connectTimeout, $readTimeout);
        }

        $time = microtime(true) - $start;
        if ($this->stopwatch) {
            $this->stopwatch->stop('algolia_transaction');
        }

        $this->transactions[$transactionId] = [
            'mocked' => $this->disableRequests,
            'request' => $request,
            'response' => $response,
            'ms' => round($time * 1000),
        ];

        return $response;
    }

    /**
     * @return array
     */
    public function getTransactions()
    {
        return $this->transactions;
    }
}
