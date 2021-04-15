<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Adapter;

use Amasty\Xsearch\Block\Search\Product as ProductBlock;
use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\SharedCatalog\Resolver;
use Amasty\Xsearch\Model\SharedCatalog\SharedCatalog;
use Magento\Framework\Search\Request\BuilderFactory;
use Magento\Framework\UrlInterface;
use Magento\Search\Model\AdapterFactory;
use Magento\Store\Model\StoreManagerInterface;

class Product
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var null|Resolver
     */
    private $sharedCatalog;

    /**
     * @var BuilderFactory
     */
    private $requestBuilderFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AdapterFactory
     */
    private $adapterFactory;

    public function __construct(
        Config $config,
        BuilderFactory $requestBuilderFactory,
        StoreManagerInterface $storeManager,
        AdapterFactory $adapterFactory,
        SharedCatalog $sharedCatalog
    ) {
        $this->config = $config;
        $this->requestBuilderFactory = $requestBuilderFactory;
        $this->sharedCatalog = $sharedCatalog->get();
        $this->storeManager = $storeManager;
        $this->adapterFactory = $adapterFactory;
    }

    public function getProductIndex(string $searchQuery, string $type): array
    {
        $limit = (int) $this->config->getModuleConfig($type . '/limit');
        $requestBuilder = $this->requestBuilderFactory->create();
        $scope = $this->storeManager->getStore()->getId();
        $requestBuilder->bindDimension('scope', $scope);
        $requestBuilder->setRequestName('quick_search_container');
        $requestBuilder->bind('visibility', [3, 4]);
        $requestBuilder->bind('search_term', $searchQuery);
        if (!$this->sharedCatalog) {
            $requestBuilder->setSize($limit);
        }
        $request = $requestBuilder->create();
        $searchResponse = $this->adapterFactory->create()->queryAdvancedSearchProduct($request);
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        if ($this->sharedCatalog) {
            $searchResponse = $this->sharedCatalog->resolve($searchResponse);
        }
        foreach ($searchResponse['products'] as &$product) {
            if (!empty($product['img'])) {
                $product['img'] = str_replace(ProductBlock::MEDIA_URL_PLACEHOLDER, $mediaUrl, $product['img']);
            }
        }

        return $searchResponse;
    }

    public function sortProducts(array $products): array
    {
        if ($this->config->isShowOutOfStockLast()) {
            $outOfStockProducts = [];
            foreach ($products as $key => $product) {
                if (!$product['is_salable']) {
                    $outOfStockProducts[] = $product;
                    unset($products[$key]);
                }
            }

            $products = array_merge($products, $outOfStockProducts);
        }

        return $products;
    }
}
