<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Model\Search\Category;

use Amasty\Base\Model\Serializer;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\Cache\Type\Block as BlockCache;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

class UrlDataProvider
{
    const SEARCH_POPUP_CATEGORY_DATA = 'amasty_search_popup_category_data_for_store_';

    /**
     * @var BlockCache
     */
    private $cache;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Url
     */
    private $urlBuilder;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    public function __construct(
        BlockCache $cache,
        StoreManager $storeManager,
        Serializer $serializer,
        CategoryCollectionFactory $categoryCollectionFactory,
        Url $urlBuilder
    ) {
        $this->cache = $cache;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->urlBuilder = $urlBuilder;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getSearchPopupCategoryData()
    {
        if ($cachedData = $this->loadFromCache()) {
            return $cachedData;
        }

        $categoryData = [];
        $categoryCollection = $this->categoryCollectionFactory
            ->create()
            ->addNameToResult()
            ->addUrlRewriteToResult();

        /**
         * @var Category $category
         */
        foreach ($categoryCollection as $category) {
            $categoryData[$category->getId()] = [
                'name' => $category->getName(),
                'full_link' => $this->prepareCategoryLink($category)
            ];
        }

        if ($categoryData) {
            $this->saveToCache($categoryData);
        }

        return $categoryData;
    }

    /**
     * @param array $categoryData
     * @throws NoSuchEntityException
     */
    private function saveToCache(array &$categoryData)
    {
        $this->cache->save($this->serializer->serialize($categoryData), $this->getCacheTag());
    }

    /**
     * @param Category $category
     * @return string
     */
    private function prepareCategoryLink(Category $category)
    {
        if ($category->getRequestPath()) {
            $categoryLink = $category->getUrlInstance()->getDirectUrl($category->getRequestPath());
        } else {
            $urlKey = $category->getUrlKey() ? $category->getUrlKey() : $category->formatUrlKey($category->getName());
            $categoryLink = $this->urlBuilder->getUrl(
                'catalog/category/view',
                ['s' => $urlKey, 'id' => $category->getId()]
            );
        }

        return $categoryLink;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function getCacheTag()
    {
        $storeId = $this->storeManager->getStore()->getId();

        return self::SEARCH_POPUP_CATEGORY_DATA . $storeId;
    }

    /**
     * @return array
     */
    private function loadFromCache()
    {
        try {
            $cachedData = $this->cache->load($this->getCacheTag());

            if ($cachedData) {
                return $this->serializer->unserialize($cachedData);
            }
        } catch (\Exception $e) {
            return [];
        }

        return [];
    }
}
