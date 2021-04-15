<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Plugin\CatalogSearch\Model\Indexer\Fulltext\Action;

use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider as MagentoDataProvider;
use Amasty\Xsearch\Model\CatalogSearch\Indexer\Fulltext\DataProvider as AmastyDataProvider;

class DataProviderPlugin
{
    private $amastyDataProvider;

    public function __construct(AmastyDataProvider $amastyDataProvider)
    {
        $this->amastyDataProvider = $amastyDataProvider;
    }

    /**
     * Plugin cuts off products which, don't have stock data for current website. This action is necessary for
     * search request proper work.
     *
     * @param MagentoDataProvider $subject
     * @param callable $proceed
     * @param string $storeId
     * @param array $staticFields
     * @param array|null $productIds
     * @param int|string $lastProductId
     * @param int|string $batchSize
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundGetSearchableProducts(
        MagentoDataProvider $subject,
        callable $proceed,
        string $storeId,
        array $staticFields,
        $productIds = null,
        $lastProductId = 0,
        $batchSize = 100
    ): array {
        return $this->amastyDataProvider->getSearchableProducts(
            (int)$storeId,
            $staticFields,
            $productIds,
            (int)$lastProductId,
            (int)$batchSize
        );
    }
}
