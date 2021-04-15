<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\Search\GetRequestQuery;

use Amasty\ElasticSearch\Model\Indexer\Data\External\RelevanceBoostDataMapper;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Customer\Model\Session;
use Magento\Eav\Model\Config;
use Magento\Framework\Registry;
use Magento\Framework\Search\RequestInterface;
use Magento\Store\Model\StoreManager;

class SortingProvider
{
    const DEFAULT_SORTING = 'relevance';
    const DEFAULT_DIRECTION = 'desc';

    /**
     * List of fields that need to skipp by default.
     */
    private $skippedFields = ['entity_id'];

    /**
     * Default mapping for special fields.
     */
    private $map = ['relevance' => '_score'];

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var array
     */
    private $requestNamesForApplyRelevanceRules;

    public function __construct(
        Config $eavConfig,
        Session $customerSession,
        Registry $registry,
        StoreManager $storeManager,
        array $skippedFields = [],
        array $map = [],
        array $requestNamesForApplyRelevanceRules = []
    ) {
        $this->eavConfig = $eavConfig;
        $this->customerSession = $customerSession;
        $this->coreRegistry = $registry;
        $this->storeManager = $storeManager;
        $this->skippedFields = array_merge($this->skippedFields, $skippedFields);
        $this->map = array_merge($this->map, $map);
        $this->requestNamesForApplyRelevanceRules = array_unique($requestNamesForApplyRelevanceRules);
    }

    /**
     * @param RequestInterface $request
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(RequestInterface $request)
    {
        $sortings = [];

        foreach ($this->getRequestedSorting($request) as $item) {
            if (!$item['field'] || in_array($item['field'], $this->skippedFields)) {
                continue;
            }
            $attributeCode = $item['field'];
            $attribute = $this->eavConfig->getAttribute(
                ProductAttributeInterface::ENTITY_TYPE_CODE,
                $attributeCode
            );
            $fieldName = $this->getFieldName($attributeCode);
            if (isset($this->map[$fieldName])) {
                $fieldName = $this->map[$fieldName];
            }
            if ($attribute->getUsedForSortBy()
                && !in_array($attribute->getBackendType(), ['int', 'smallint', 'decimal'], true)
            ) {
                $fieldName .= '.sort_' . $attributeCode;
            }
            $sortings[] = [
                $fieldName => [
                    'order' => strtolower($item['direction'])
                ]
            ];
        }

        return $sortings;
    }

    /**
     * @param RequestInterface $request
     * @return array
     */
    public function getRequestedSorting(RequestInterface $request)
    {
        $result = [];

        if (method_exists($request, 'getSort') && $request->getSort()) {
            $result = $request->getSort();
        }

        if (empty($result)) {
            $result[] = ['field' => self::DEFAULT_SORTING, 'direction' => self::DEFAULT_DIRECTION];
        }

        if ($this->isSortedByRelevance($result) && $this->isCanApplyRelevanceSorting($request)) {
            array_unshift(
                $result,
                [
                    'field' => RelevanceBoostDataMapper::ATTRIBUTE_NAME,
                    'direction' => $this->getRelevanceDirection($result)
                ]
            );
        }

        return $result;
    }

    private function isSortedByRelevance(array $sortingArray): bool
    {
        return $this->getRelevanceSortingNode($sortingArray) !== null;
    }

    private function getRelevanceSortingNode(array $sortingArray): ?array
    {
        $result = null;

        foreach ($sortingArray as $sortingNode) {
            $sortOrder = $sortingNode['field'] ?? '';

            if ($sortOrder === SortingProvider::DEFAULT_SORTING) {
                $result = $sortingNode;
                break;
            }
        }

        return $result;
    }

    private function getRelevanceDirection(array $sortingArray): string
    {
        $relevanceSorting = $this->getRelevanceSortingNode($sortingArray);

        return $relevanceSorting['direction'] ?? self::DEFAULT_DIRECTION;
    }

    /**
     * @param $attributeCode
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getFieldName($attributeCode)
    {
        if ($attributeCode === 'price') {
            $customerGroupId = $this->customerSession->getCustomerGroupId();
            return 'price_' . $customerGroupId;
        } elseif ($attributeCode === 'position') {
            $categoryId = $this->coreRegistry->registry('current_category')
                ? $this->coreRegistry->registry('current_category')->getId()
                : $this->storeManager->getStore()->getRootCategoryId();
            return 'category_position_' . $categoryId;
        }
        return $attributeCode;
    }

    public function isCanApplyRelevanceSorting(RequestInterface $request): bool
    {
        return in_array($request->getName(), $this->requestNamesForApplyRelevanceRules);
    }
}
