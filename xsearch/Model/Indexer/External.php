<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Indexer;

use Amasty\Xsearch\Controller\RegistryConstants;
use Amasty\Xsearch\Model\Client\ClientWrapper;
use Amasty\Xsearch\Model\Config;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class External implements ActionInterface
{
    /**
     * @var CacheContext
     */
    private $cacheContext;

    private $clientWrapper;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CacheContext $cacheContext,
        State $appState,
        Config $config,
        StoreManagerInterface $storeManager,
        ClientWrapper $clientWrapper,
        LoggerInterface $logger
    ) {
        $this->cacheContext = $cacheContext;
        $this->appState = $appState;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->clientWrapper = $clientWrapper;
        $this->logger = $logger;
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
            $this->clientWrapper->saveExternal();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    private function createIndex(): void
    {
        foreach ($this->storeManager->getStores() as $store) {
            $popupIndexName = $this->clientWrapper->getIndexName(
                ClientWrapper::EXTERNAL_INDEX . '_' . RegistryConstants::INDEX_ENTITY_TYPE,
                (int) $store->getId()
            );
            if (!$this->clientWrapper->indexExists($popupIndexName)) {
                $this->clientWrapper->createIndex(
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
    private function checkCorrectAreaCode(): bool
    {
        if ($this->appState->isAreaCodeEmulated()) {
            return $this->appState->getAreaCode() == \Magento\Framework\App\Area::AREA_FRONTEND;
        }

        return true;
    }
}
