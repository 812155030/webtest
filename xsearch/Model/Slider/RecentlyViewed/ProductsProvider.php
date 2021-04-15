<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Slider\RecentlyViewed;

use Amasty\Xsearch\Model\Authentication;
use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\ResourceModel\Slider\RecentlyViewed as RecentlyViewedResource;
use Amasty\Xsearch\Model\Slider\CollectionProcessor;
use Amasty\Xsearch\Model\Slider\SliderProductsProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class ProductsProvider implements SliderProductsProviderInterface
{
    /**
     * @var ProductCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductCollection
     */
    private $collection;

    /**
     * @var RecentlyViewedResource
     */
    private $recentlyViewedResource;

    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        ProductCollectionFactory $collectionFactory,
        RecentlyViewedResource $recentlyViewedResource,
        Authentication $authentication,
        CollectionProcessor $collectionProcessor,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->recentlyViewedResource = $recentlyViewedResource;
        $this->authentication = $authentication;
        $this->collectionProcessor = $collectionProcessor;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * @return Product[]
     */
    public function getProducts(): iterable
    {
        if ($this->collection === null) {
            $collection = $this->collectionFactory->create();
            $this->initializeCollection($collection);
            $this->collection = $collection;
        }

        return $this->collection;
    }

    private function initializeCollection(ProductCollection $collection): void
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $this->recentlyViewedResource->applyFilterToProductCollection(
            $this->authentication->getCustomerIdentifier(),
            $storeId,
            $collection
        );
        $this->collectionProcessor->process($collection);
        $collection->setPageSize($this->config->getRecentlyViewedBlockLimit());
    }
}
