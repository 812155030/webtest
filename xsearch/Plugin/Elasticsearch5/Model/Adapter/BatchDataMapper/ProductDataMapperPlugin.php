<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Plugin\Elasticsearch5\Model\Adapter\BatchDataMapper;

use Amasty\Xsearch\Model\Indexer\ElasticSearchStockStatusDataMapper;
use Amasty\Xsearch\Model\Indexer\ElasticSearchStockStatusStructureMapper;

class ProductDataMapperPlugin
{
    /**
     * @var ElasticSearchStockStatusDataMapper
     */
    private $mapper;

    public function __construct(ElasticSearchStockStatusDataMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Prepare index data for using in search engine metadata.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param $subject
     * @param callable $proceed
     * @param array $documentData
     * @param $storeId
     * @param array $context
     * @return array
     */
    public function aroundMap(
        $subject,
        callable $proceed,
        array $documentData,
        $storeId,
        $context = []
    ): array {
        $documentData = $proceed($documentData, $storeId, $context);

        $stockData = $this->mapper->map($documentData, $storeId, $context);
        $fieldName = ElasticSearchStockStatusStructureMapper::STOCK_STATUS;
        foreach ($documentData as $productId => $document) {
            if (isset($stockData[$productId])) {
                $documentData[$productId][$fieldName] = $stockData[$productId][$fieldName];
            }
        }

        return $documentData;
    }
}
