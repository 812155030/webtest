<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\Search;

use Amasty\ElasticSearch\Model\Client\ClientRepositoryInterface;
use Amasty\ElasticSearch\Model\Search\GetResponse\GetAggregations;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Magento\Framework\Registry as CoreRegistry;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;

class Adapter implements AdapterInterface
{
    const REQUEST_QUERY = 'amasty_elastic_query';
    const HITS = 'hits';
    const PRODUCTS = 'products';

    /**
     * @var GetRequestQuery
     */
    private $getRequestQuery;

    /**
     * @var GetResponse
     */
    private $getElasticResponse;

    /**
     * @var GetAggregations
     */
    private $getAggregations;

    /**
     * @var ClientRepositoryInterface
     */
    private $clientRepository;

    /**
     * @var CoreRegistry
     */
    private $registry;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        ClientRepositoryInterface $clientRepository,
        GetAggregations $getAggregations,
        GetRequestQuery $getRequestQuery,
        GetResponse $getElasticResponse,
        CoreRegistry $registry,
        Logger $logger
    ) {
        $this->getAggregations = $getAggregations;
        $this->getRequestQuery = $getRequestQuery;
        $this->getElasticResponse = $getElasticResponse;
        $this->clientRepository = $clientRepository;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * @param RequestInterface $request
     * @return \Magento\Framework\Search\Response\QueryResponse|mixed
     * @throws \Elasticsearch\Common\Exceptions\Missing404Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function query(RequestInterface $request)
    {
        $client = $this->clientRepository->get();

        if (!$client->getClient()->ping()) {
            return $this->getElasticResponse->execute([], [], 0);
        }

        try {
            $requestQuery = $this->getRequestQuery->execute($request);
            $elasticResponse = $client->search($requestQuery);
        } catch (BadRequest400Exception $e) {
            $this->logger->logError($e->getMessage());
            return $this->getElasticResponse->execute([], [], 0);
        } catch (\Exception $e) {
            return $this->getElasticResponse->execute([], [], 0);
        }

        $elasticDocuments = $elasticResponse['hits']['hits'] ?? [];
        $elasticTotal = $elasticResponse['hits']['total']['value'] ?? $elasticResponse['hits']['total'] ?? 0;
        $this->registry->unregister(self::REQUEST_QUERY);
        $this->registry->register(self::REQUEST_QUERY, $requestQuery['body']['query']);
        $aggregations = $this->getAggregations->execute($request, $elasticResponse);
        $this->registry->unregister(self::REQUEST_QUERY);
        $responseQuery = $this->getElasticResponse->execute($elasticDocuments, $aggregations, $elasticTotal);
        $this->logger->log($request, $responseQuery, $requestQuery, $elasticResponse);

        return $responseQuery;
    }

    /**
     * @param RequestInterface $request
     * @return array
     * @throws \Elasticsearch\Common\Exceptions\Missing404Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function queryAdvancedSearchProduct(RequestInterface $request)
    {
        $client = $this->clientRepository->get();
        $requestQuery = $this->getRequestQuery->execute($request);
        unset($requestQuery['aggregations']);

        $requestQuery['body']['_source'] = ['amasty_xsearch_fulltext'];
        $elasticResponse = $client->search($requestQuery);
        $products = [];
        if (!empty($elasticResponse['hits']['hits'])) {
            foreach ($elasticResponse['hits']['hits'] as $index => $product) {
                if (!empty($product['_source']['amasty_xsearch_fulltext'])) {
                    $products[$product['_id']] = $product['_source']['amasty_xsearch_fulltext'];
                }
            }
        }

        $hits = $elasticResponse['hits']['total']['value'] ?? $elasticResponse['hits']['total'] ?? 0;
        return [self::HITS => $hits, self::PRODUCTS => $products];
    }
}
