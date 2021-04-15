<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


declare(strict_types=1);

namespace Amasty\ElasticSearch\Ui\RelevanceRule\DataProvider\Product\Filter;

use Magento\CatalogRule\Model\RuleFactory as RelevanceRuleConditionsFactory;
use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;
use Zend\Uri\Uri as ZendUri;

class RuleConditionFilter implements AddFilterToCollectionInterface
{
    const MATCHED_FLAG = 'matched_products';
    const DEFAULT_WEBSITE = 0;

    /**
     * @var RelevanceRuleConditionsFactory
     */
    private $relevanceRuleFactory;

    /**
     * @var ZendUri
     */
    private $zendUri;

    public function __construct(
        RelevanceRuleConditionsFactory $relevanceRuleFactory,
        ZendUri $zendUri
    ) {
        $this->relevanceRuleFactory = $relevanceRuleFactory;
        $this->zendUri = $zendUri;
    }

    public function addFilter(Collection $collection, $field, $condition = null)
    {
        $conditions = $this->conditionArray($condition['eq'] ?? '');

        if (empty($condition)) {
            $collection->addIdFilter(null);
        } else {
            $matchedIds = $this->getMatchedProductsIds($conditions);

            if (!empty($matchedIds)) {
                $collection->addIdFilter($matchedIds);
            } else {
                $collection->getSelect()->where('null');
            }
        }
    }

    /**
     * @param array[] $conditions
     *
     * @return int[]
     */
    public function getMatchedProductsIds(array $conditions): array
    {
        $matchedProductIds = [];
        $rule = $this->relevanceRuleFactory->create();
        $rule->loadPost($conditions['rule'] ?? []);
        $website = $conditions['websites'] ?? [self::DEFAULT_WEBSITE];
        $rule->setWebsiteIds(join(',', $website));

        if (!$this->isRuleEmpty($rule)) {
            $matchedProducts = $rule->getMatchingProductIds();
            $matchedProductIds = array_map('intval', array_unique(array_keys($matchedProducts)));
        }

        return $matchedProductIds;
    }

    private function isRuleEmpty(\Magento\CatalogRule\Model\Rule $rule): bool
    {
        $conditionsArray = $rule->getConditions()->asArray();

        return empty($conditionsArray['conditions']);
    }

    private function conditionArray(string $serializedConditions): array
    {
        $this->zendUri->setQuery($serializedConditions);

        return $this->zendUri->getQueryAsArray();
    }
}
