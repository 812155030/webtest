<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\Indexer\Xsearch;

use Amasty\Xsearch\Controller\RegistryConstants;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\ActionInterface;
use Amasty\ElasticSearch\Model\Client\Elasticsearch as Client;

class Fulltext implements ActionInterface
{
    /**
     * @var \Magento\Framework\Indexer\CacheContext
     */
    private $cacheContext;

    /**
     * @var Client
     */
    private $elasticClient;

    /**
     * @var \Amasty\ElasticSearch\Model\Debug
     */
    private $debug;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Amasty\ElasticSearch\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        \Magento\Framework\Indexer\CacheContext $cacheContext,
        \Amasty\ElasticSearch\Model\Debug $debug,
        \Magento\Framework\App\State $appState,
        \Amasty\ElasticSearch\Model\Config $config,
        Client $elasticClient,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->cacheContext = $cacheContext;
        $this->elasticClient = $elasticClient;
        $this->debug = $debug;
        $this->appState = $appState;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    public function executeFull()
    {
        if (!$this->checkCorrectAreaCode() || !$this->config->isElasticEngine()) {
            return $this;
        }

        try {
            $this->createIndex();
            $this->elasticClient->saveExternal();
        } catch (\Exception $e) {
            $this->debug->debug($e);
        }
    }

    private function createIndex()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $popupIndexName = $this->elasticClient->getIndexName(
                Client::EXTERNAL_INDEX . '_' . RegistryConstants::INDEX_ENTITY_TYPE,
                $store->getId()
            );
            if (!$this->elasticClient->indexExists($popupIndexName)) {
                $this->elasticClient->createIndex(
                    $popupIndexName,
                    []
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function executeRow($id)
    {
        try {
            $this->executeFull();
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * @inheritdoc
     */
    public function executeList(array $ids)
    {
        try {
            $this->executeFull();
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    private function checkCorrectAreaCode()
    {
        if ($this->appState->isAreaCodeEmulated()) {
            return $this->appState->getAreaCode() == \Magento\Framework\App\Area::AREA_FRONTEND;
        }
        return true;
    }
}
