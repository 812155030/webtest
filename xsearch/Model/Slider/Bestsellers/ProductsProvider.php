<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Slider\Bestsellers;

use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\Di\Wrapper as BestsellersMethodDiProxy;
use Amasty\Xsearch\Model\Slider\CollectionProcessor;
use Amasty\Xsearch\Model\Slider\SliderProductsProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DB\Select;

class ProductsProvider implements SliderProductsProviderInterface
{
    /**
     * @var \Amasty\Sorting\Model\ResourceModel\Method\Bestselling
     */
    private $sortingBestsellersMethod;

    /**
     * @var ProductCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProductCollection
     */
    private $collection;

    public function __construct(
        BestsellersMethodDiProxy $sortingBestsellersMethod,
        ProductCollectionFactory $collectionFactory,
        CollectionProcessor $collectionProcessor,
        Config $config
    ) {
        $this->sortingBestsellersMethod = $sortingBestsellersMethod;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->config = $config;
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
        $this->getSortingBestsellersMethod()->apply($collection, Select::SQL_DESC);
        $this->collectionProcessor->process($collection);
        $collection->setPageSize($this->config->getBestsellersBlockProductsLimit());
    }

    /**
     * Returns safe DI proxy for Amasty_Sorting bestselling sorting method
     * @see \Amasty\Sorting\Model\ResourceModel\Method\Bestselling::apply
     *
     * @return \Amasty\Sorting\Model\ResourceModel\Method\Bestselling
     */
    private function getSortingBestsellersMethod()
    {
        return $this->sortingBestsellersMethod;
    }
}
