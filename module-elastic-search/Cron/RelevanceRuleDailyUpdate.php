<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


declare(strict_types=1);

namespace Amasty\ElasticSearch\Cron;

use Amasty\ElasticSearch\Api\Data\RelevanceRuleInterface;
use Amasty\ElasticSearch\Api\RelevanceRuleRepositoryInterface;
use Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule\CollectionFactory;

class RelevanceRuleDailyUpdate
{
    /**
     * @var CollectionFactory
     */
    private $ruleCollectionFactory;

    /**
     * @var RelevanceRuleRepositoryInterface
     */
    private $relevanceRuleRepository;

    public function __construct(
        CollectionFactory $ruleCollectionFactory,
        RelevanceRuleRepositoryInterface $relevanceRuleRepository
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->relevanceRuleRepository = $relevanceRuleRepository;
    }

    public function execute(): void
    {
        foreach ($this->getRulesForEnable() as $relevanceRule) {
            $relevanceRule->setIsEnabled(true);
            $this->relevanceRuleRepository->save($relevanceRule);
        }

        foreach ($this->getRulesForDisable() as $relevanceRule) {
            $relevanceRule->setIsEnabled(false);
            $this->relevanceRuleRepository->save($relevanceRule);
        }
    }

    /**
     * @return RelevanceRuleInterface[]
     */
    private function getRulesForEnable(): iterable
    {
        $collection = $this->ruleCollectionFactory->create();
        $collection->addStatusFilter(false);
        $collection->addFieldToFilter(
            RelevanceRuleInterface::FROM_DATE,
            ['lt' => new \Zend_Db_Expr('NOW()')]
        );
        $collection->addFieldToFilter(
            RelevanceRuleInterface::TO_DATE,
            ['gt' => new \Zend_Db_Expr('NOW()')]
        );

        return $collection;
    }

    /**
     * @return RelevanceRuleInterface[]
     */
    private function getRulesForDisable(): iterable
    {
        $collection = $this->ruleCollectionFactory->create();
        $collection->addStatusFilter(true);
        $collection->addFieldToFilter(
            RelevanceRuleInterface::TO_DATE,
            ['lt' => new \Zend_Db_Expr('NOW()')]
        );

        return $collection;
    }
}
