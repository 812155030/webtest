<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


declare(strict_types=1);

namespace Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule;

use Amasty\ElasticSearch\Api\Data\RelevanceRuleInterface;
use Amasty\ElasticSearch\Model\Indexer\RelevanceRule\IndexBuilder;
use Magento\Framework\App\ResourceConnection;

class BoostMultipliersProvider
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param int $websiteId
     * @param int[]|null $productIds
     *
     * @return float[]
     */
    public function getBoostMultipliers(int $websiteId, ?array $productIds = null): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from($this->resourceConnection->getTableName(IndexBuilder::TABLE_NAME), []);
        $select->columns([
            IndexBuilder::PRODUCT_ID => IndexBuilder::PRODUCT_ID,
            RelevanceRuleInterface::MULTIPLIER => new \Zend_Db_Expr(sprintf(
                'GREATEST(SUM(GREATEST(%1$s, 0)), 1) / GREATEST(ABS(SUM(LEAST((%1$s), 0))), 1)',
                RelevanceRuleInterface::MULTIPLIER
            ))
        ]);
        $select->where(
            $connection->prepareSqlCondition(RelevanceRuleInterface::WEBSITE_ID, ['eq' => $websiteId])
        );
        $select->group(IndexBuilder::PRODUCT_ID);

        if ($productIds !== null) {
            $select->where(
                $connection->prepareSqlCondition(IndexBuilder::PRODUCT_ID, ['in' => $productIds])
            );
        }

        $rawMultipliers = (array) $connection->fetchPairs($select);

        return array_map('floatval', $rawMultipliers);
    }
}
