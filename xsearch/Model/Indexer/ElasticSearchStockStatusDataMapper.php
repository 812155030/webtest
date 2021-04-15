<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Indexer;

use Amasty\Xsearch\Model\Indexer\ElasticSearchStockStatusStructureMapper;

class ElasticSearchStockStatusDataMapper
{
    const STOCK_IN_STOCK = 1;

    const STOCK_OUT_OF_STOCK = 2;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var array
     */
    private $inStockProductIds = [];

    /**
     * @var \Magento\CatalogInventory\Model\ResourceModel\Stock\Status
     */
    private $stockStatusResource;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\Status $stockStatusResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockStatusResource = $stockStatusResource;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param array $documentData
     * @param int $storeId
     * @param array $context
     * @return array
     */
    public function map(array $documentData, $storeId, array $context = [])
    {
        $stockStatusDocumentData = [];
        $fieldName = ElasticSearchStockStatusStructureMapper::STOCK_STATUS;
        foreach ($documentData as $productId => $document) {
            if (!isset($document[$fieldName])) {
                $stockStatus = $this->isProductInStock($productId, (int)$storeId);
                $stockStatusDocumentData[$productId][$fieldName] = $stockStatus;
            }
        }

        return $stockStatusDocumentData;
    }

    /**
     * @param int $entityId
     * @param int $storeId
     * @return int
     */
    private function isProductInStock(int $entityId, int $storeId): int
    {
        if (in_array($entityId, $this->getInStockProductIds($storeId))) {
            return self::STOCK_IN_STOCK;
        }

        return self::STOCK_OUT_OF_STOCK;
    }

    /**
     * @param int $storeId
     * @return array
     */
    private function getInStockProductIds($storeId): array
    {
        if (!isset($this->inStockProductIds[$storeId])) {
            $collection = $this->productCollectionFactory->create()->addStoreFilter($storeId);
            $this->stockStatusResource->addStockDataToCollection($collection, true);
            $this->inStockProductIds[$storeId] = $collection->getAllIds();
        }

        return $this->inStockProductIds[$storeId];
    }
}
