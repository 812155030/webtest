<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule;

use Amasty\Base\Model\Serializer;
use Amasty\ElasticSearch\Api\Data\RelevanceRuleInterface;
use Amasty\ElasticSearch\Model\RelevanceRule as RelevanceRuleResource;
use Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Psr\Log\LoggerInterface;

class Collection extends AbstractCollection
{
    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Serializer $serializer,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_idFieldName = RelevanceRuleInterface::RULE_ID;
        $this->_init(
            RelevanceRuleResource::class,
            RelevanceRule::class
        );
    }

    /**
     * @return $this
     */
    public function addActiveFilter(): Collection
    {
        $this->addStatusFilter(true);

        return $this;
    }

    public function addStatusFilter(bool $active): void
    {
        $this->addFieldToFilter(RelevanceRuleInterface::IS_ENABLED, ['eq' => (int) $active]);
    }

    /**
     * @param string $attributeCode
     * @return $this
     */
    public function addAttributeInConditionFilter($attributeCode): Collection
    {
        $match = sprintf(
            '%%%s%%',
            substr($this->serializer->serialize(['attribute' => $attributeCode]), 1, -1)
        );
        $this->addFieldToFilter(RelevanceRuleInterface::CONDITIONS, ['like' => $match]);

        return $this;
    }
}
