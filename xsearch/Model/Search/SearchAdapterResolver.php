<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Search;

use Amasty\Xsearch\Block\Search\Product;
use Amasty\Xsearch\Helper\Data;
use Amasty\Xsearch\Model\Adapter\Product as ProductAdapter;
use Amasty\Xsearch\Model\Client\Factory;
use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\Indexer\ElasticExternalProvider;
use Magento\Search\Model\QueryInterface;
use Magento\Store\Model\StoreManagerInterface;

class SearchAdapterResolver
{
    /**
     * @var array
     */
    private $results = [];

    /**
     * @var GetRequestQuery
     */
    private $getRequestQuery;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $indexedTypes;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Factory
     */
    private $clientFactory;

    /**
     * @var ProductAdapter
     */
    private $productAdapter;

    /**
     * @var Data
     */
    private $helper;

    public function __construct(
        GetRequestQuery $getRequestQuery,
        StoreManagerInterface $storeManager,
        Config $config,
        Factory $clientFactory,
        ProductAdapter $productAdapter,
        Data $helper,
        array $indexedTypes = []
    ) {
        $this->getRequestQuery = $getRequestQuery;
        $this->storeManager = $storeManager;
        $this->indexedTypes = $indexedTypes;
        $this->config = $config;
        $this->clientFactory = $clientFactory;
        $this->helper = $helper;
        $this->productAdapter = $productAdapter;
    }

    public function getResults(string $type, QueryInterface $query): array
    {
        return $this->config->isEnablePopupIndex() ? $this->getResultsFromIndex($type, $query) : [];
    }

    private function getResultsFromIndex(string $type, QueryInterface $query): array
    {
        $results = false;
        if (in_array($type, $this->indexedTypes) && $this->config->isElasticEngine()) {
            $query = $query->getQueryText();
            $query = str_replace('~', '', $query);
            if ($type === Product::BLOCK_TYPE) {
                if ($this->config->isAmastyElasticEngine()) {
                    $results = $this->productAdapter->getProductIndex($query, $type);
                    $results = $results['products'];
                }
            } else {
                $results = $this->getIndexedItems($query, $type);
            }
        }

        return $results ? $this->prepareResponse($results, $type, $query) : [];
    }

    /**
     * @param string $searchQuery
     * @param string $indexType
     * @return mixed
     * @throws \Elasticsearch\Common\Exceptions\Missing404Exception
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getIndexedItems(string $searchQuery, string $indexType)
    {
        if (!isset($this->results[$searchQuery])) {
            $searchQuery = str_replace('"', '\"', preg_quote($searchQuery, '/'));
            foreach ($this->indexedTypes as $label) {
                $this->results[$searchQuery][$label] = [];
            }

            $elasticResponse = $this->clientFactory->getClient()->query($this->modifyQuery($searchQuery));
            $documents = [];
            if (isset($elasticResponse['hits']['hits'])) {
                $documents = array_map(function ($item) {
                    return $item['_source'];
                }, $elasticResponse['hits']['hits']);
            }

            foreach ($documents as $document) {
                $type = $document[ElasticExternalProvider::BLOCK_TYPE_FIELD];
                unset(
                    $document[ElasticExternalProvider::BLOCK_TYPE_FIELD],
                    $document[ElasticExternalProvider::FULLTEXT_INDEX_FIELD]
                );

                $this->results[$searchQuery][$type][] = $document;
            }
        }

        return $this->results[$searchQuery][$indexType];
    }

    private function modifyQuery(string $searchQuery): array
    {
        $queryArray = array_map(function ($item) {
            return mb_strlen($item) > 2 ? $item . '*' : $item;
        }, array_filter(explode(' ', $searchQuery)));
        $elasticQuery = implode(' OR ', $queryArray);

        return $this->getRequestQuery->executeExternalByFulltext(
            $elasticQuery,
            (int) $this->storeManager->getStore()->getId(),
            ElasticExternalProvider::FULLTEXT_INDEX_FIELD,
            \Amasty\Xsearch\Controller\RegistryConstants::INDEX_ENTITY_TYPE
        );
    }

    private function prepareResponse(array $response, string $type, string $queryText): array
    {
        if ($limit = (int) $this->config->getModuleConfig($type . '/limit')) {
            $response = array_slice($response, 0, $limit);
        }

        if ($type === Product::BLOCK_TYPE) {
            $response = $this->productAdapter->sortProducts($response);
        }

        foreach ($response as &$item) {
            if (isset($item['name'])) {
                $item['name'] = $this->helper->highlight($item['name'], $queryText);
            }

            if (isset($item['title'])) {
                $item['title'] = $this->helper->highlight($item['title'], $queryText);
            }

            if (isset($item['description'])) {
                $item['description'] = $this->helper->highlight($item['description'], $queryText);
            }
        }

        return $response;
    }
}
