<?php

namespace Algolia\AlgoliaSearchBundle\DataCollector;

use Algolia\AlgoliaSearchBundle\Debug\DebugClientInterface;
use AlgoliaSearch\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class ClientDataCollector extends DataCollector
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'transactions' => [],
            'transactionCount' => [],
        ];

        if (! $this->client instanceof DebugClientInterface) {
            return;
        }

        $transactions = $this->client->getTransactions();

        $this->data = [
            'transactions' => $transactions,
            'transactionCount' => count($transactions),
        ];
    }

    /**
     * @return array
     */
    public function getTransactions()
    {
        return $this->data['transactions'];
    }

    /**
     * @return int
     */
    public function getTransactionCount()
    {
        return $this->data['transactionCount'];
    }

    /**
     * @return int
     */
    public function getTotalTime()
    {
        $time = 0;

        foreach ($this->data['transactions'] as $query) {
            $time += $query['ms'];
        }

        return (int) $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'algolia.client_data_collector';
    }
}
