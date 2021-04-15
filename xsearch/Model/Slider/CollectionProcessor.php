<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Slider;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class CollectionProcessor
{
    /**
     * @var StockHelper
     */
    private $stockHelper;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Visibility
     */
    private $productVisibility;

    public function __construct(
        StockHelper $stockHelper,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        Visibility $productVisibility
    ) {
        $this->stockHelper = $stockHelper;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->productVisibility = $productVisibility;
    }

    public function process(ProductCollection $collection): void
    {
        $currentStore = $this->storeManager->getStore();
        $this->stockHelper->addIsInStockFilterToCollection($collection);
        $collection->addPriceData();
        $this->addAttributes($collection);
        $this->addReviews($collection);
        $collection->setVisibility($this->productVisibility->getVisibleInCatalogIds());
        $collection->setStore($currentStore);
        $collection->addUrlRewrite($currentStore->getRootCategoryId());
    }

    private function addReviews($collection): void
    {
        $this->eventManager->dispatch(
            'catalog_block_product_list_collection',
            ['collection' => $collection]
        );
    }

    /**
     * @param ProductCollection $collection
     */
    private function addAttributes(ProductCollection $collection): void
    {
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('image');
    }
}
