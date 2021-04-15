<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Client;

use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\Indexer\ExternalIndexerProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class ClientWrapper
{
    const BULK_ACTION_INDEX = 'index';
    const EXTERNAL_INDEX = 'external';

    const DOCUMENT_TYPE = 'document';

    /**
     * @var Config
     */
    private $config;

    private $elasticClient;

    /**
     * @var array
     */
    private $indexNameByStore = [];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ExternalIndexerProvider
     */
    private $externalIndexerProvider;

    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager,
        ExternalIndexerProvider $externalIndexerProvider,
        Factory $factory
    ) {
        $this->elasticClient = $factory->getClient();
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->externalIndexerProvider = $externalIndexerProvider;
    }

    /**
     * @throws LocalizedException
     * @return $this
     */
    public function saveExternal(): self
    {
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();
            $documents = $this->externalIndexerProvider->getDocuments($storeId);
            foreach (array_keys($documents) as $indexType) {
                if (empty($documents[$indexType])) {
                    continue;
                }

                $indexName = $this->getIndexName(self::EXTERNAL_INDEX . '_' . $indexType, $storeId);
                if ($this->indexExists($indexName)) {
                    $this->deleteIndex($indexName);
                }

                $query = $this->prepareSaveQuery($documents[$indexType], $indexName);
                $this->bulk($query);
            }
        }

        return $this;
    }

    private function prepareSaveQuery(
        array $documents,
        string $indexName,
        string $action = self::BULK_ACTION_INDEX
    ): array {
        $bulkArray = [
            'index' => $indexName,
            'type' => self::DOCUMENT_TYPE,
            'body' => [],
            'refresh' => true,
        ];

        foreach ($documents as $id => $document) {
            $bulkArray['body'][] = [
                $action => [
                    '_id' => $id,
                    '_type' => self::DOCUMENT_TYPE,
                    '_index' => $indexName
                ]
            ];
            if ($action == self::BULK_ACTION_INDEX) {
                $bulkArray['body'][] = $document;
            }
        }

        return $bulkArray;
    }

    private function bulk(array $query): ?array
    {
        $result = $this->elasticClient->bulkQuery($query);
        if (!empty($result['errors'])) {
            $message = __('Elasticsearch engine returned an error response. ');
            if (!empty($result['items'])) {
                foreach ($result['items'] as $item) {
                    $item = $item['index'];
                    if (!empty($item['error'])) {
                        $causedBy = '';
                        if (!empty($item['error']['caused_by'])) {
                            $causedBy = ', caused by: "' . $item['error']['caused_by']['type']
                                . '. ' . $item['error']['caused_by']['reason'] . '"';
                        }

                        $message .= __(
                            'item id: %1. Error type: "%2", reason "%3"%4.' . PHP_EOL,
                            $item['_id'],
                            $item['error']['type'],
                            $item['error']['reason'],
                            $causedBy
                        );
                    }
                }
            }
            //@codingStandardsIgnoreLine
            throw new \Exception($message);
        }

        return $result;
    }

    public function getIndexName(string $indexerId, int $storeId): string
    {
        if (strpos($indexerId, self::EXTERNAL_INDEX) === 0) {
            return $this->getIndexAlias($indexerId, $storeId);
        }

        if (!isset($this->indexNameByStore[$storeId])) {
            $alias = $this->getIndexAlias($indexerId, $storeId);
            $indexName = $this->getIndexNameByAlias($alias, $storeId);

            if (empty($indexName)) {
                $indexName = $alias . '_v1';
            }

            return $indexName;
        }

        return $this->indexNameByStore[$storeId];
    }

    public function getIndexAlias(string $indexerId, int $storeId): string
    {
        return $this->config->getIndexName($indexerId, $storeId);
    }

    public function getIndexNameByAlias(string $alias, int $storeId): string
    {
        $storeIndex = '';
        $indexPattern = $alias . '_v';
        if ($this->existsAlias($alias)) {
            $alias = $this->getAlias($alias);
            $indices = array_keys($alias);
            natsort($indices);
            foreach ($indices as $index) {
                if (strpos($index, $indexPattern) === 0) {
                    if (isset($this->indexNameByStore[$storeId]) && $this->indexNameByStore[$storeId] == $index) {
                        continue;
                    }
                    $storeIndex = $index;
                    break;
                }
            }
        }

        return $storeIndex;
    }

    private function existsAlias(string $alias, string $index = ''): bool
    {
        return $this->elasticClient->existsAlias($alias, $index);
    }

    private function getAlias(string $alias): array
    {
        return $this->elasticClient->getAlias($alias);
    }

    public function indexExists(string $alias): bool
    {
        return $this->elasticClient->indexExists($alias);
    }

    public function createIndex(string $index, array $settings): void
    {
        $this->elasticClient->createIndex($index, $settings);
    }

    private function deleteIndex(string $index): void
    {
        $this->elasticClient->deleteIndex($index);
    }
}
