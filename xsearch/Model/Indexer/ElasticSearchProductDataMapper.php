<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Model\Indexer;

use Amasty\Xsearch\Controller\RegistryConstants;

class ElasticSearchProductDataMapper
{
    const POPUP_DATA_BATCH_SIZE = 500;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $appEmulation;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Amasty\Xsearch\Block\Search\ProductFactory
     */
    private $productBlockFactory;

    /**
     * @var \Amasty\Xsearch\Helper\Data
     */
    private $helper;

    public function __construct(
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Framework\App\State $appState,
        \Amasty\Xsearch\Block\Search\ProductFactory $productBlockFactory,
        \Amasty\Xsearch\Helper\Data $helper
    ) {
        $this->appEmulation = $appEmulation;
        $this->appState = $appState;
        $this->productBlockFactory = $productBlockFactory;
        $this->helper = $helper;
    }

    /**
     * @param array $documentData
     * @param $storeId
     * @param array $context
     * @return array|mixed
     * @throws \Exception
     */
    public function map(array $documentData, $storeId, array $context = [])
    {
        if ($this->helper->isIndexEnable(null)) {
            $productsData = $this->appState->emulateAreaCode(
                \Magento\Framework\App\Area::AREA_FRONTEND,
                [$this, 'getProductSearchData'],
                [$storeId, array_keys($documentData)]
            );
            $this->appEmulation->stopEnvironmentEmulation();
            $productsData = array_map(function ($product) {
                return [RegistryConstants::INDEX_ENTITY_TYPE => $product];
            }, $productsData);

            return $productsData;
        }

        return [];
    }

    /**
     * @param $storeId
     * @param array $productIds
     * @return array
     */
    public function getProductSearchData($storeId, array $productIds)
    {
        $this->appEmulation->startEnvironmentEmulation(
            $storeId,
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );

        $data = [];
        $productCount = count($productIds);
        $i = 0;
        while ($i < $productCount) {
            $batchedProductIds = array_slice($productIds, $i, self::POPUP_DATA_BATCH_SIZE);
            $i += self::POPUP_DATA_BATCH_SIZE;
            $result = $this->productBlockFactory->create()
                ->setLimit(0)
                ->setIndexedIds($batchedProductIds)
                ->getResults();
            $data = array_replace($data, $result);
        }

         return $data;
    }
}
