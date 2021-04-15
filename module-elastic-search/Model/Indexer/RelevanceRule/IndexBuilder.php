<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\Indexer\RelevanceRule;

use Amasty\ElasticSearch\Api\Data\RelevanceRuleInterface;
use Amasty\ElasticSearch\Api\RelevanceRuleRepositoryInterface;
use Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule\CollectionFactory as RelevanceRuleCollectionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexBuilder
{
    const SECONDS_IN_DAY = 86400;
    const PRODUCT_ID = 'product_id';
    const TABLE_NAME = 'amasty_elastic_relevance_rule_index_tmp';
    const MAX_INT_MYSQL = 4294967294;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var RelevanceRuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $batchCount;

    /**
     * @var RelevanceRuleCollectionFactory
     */
    private $relevanceRuleCollectionFactory;

    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        RelevanceRuleRepositoryInterface $ruleRepository,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        RelevanceRuleCollectionFactory $relevanceRuleCollectionFactory,
        $batchCount = 1000
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->ruleRepository = $ruleRepository;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->batchCount = $batchCount;
        $this->relevanceRuleCollectionFactory = $relevanceRuleCollectionFactory;
    }

    /**
     * Reindex by product Ids
     *
     * @param array $ids
     * @throws LocalizedException
     */
    public function reindexByIds(array $ids): void
    {
        try {
            $this->cleanByIds($ids);
            $products = $this->getProducts($ids);

            foreach ($this->getActiveRules() as $rule) {
                foreach ($products as $product) {
                    $this->applyRule($rule, $product);
                }
            }
        } catch (\Exception $e) {
            $this->critical($e);

            throw new LocalizedException(
                __("Amasty ElasticSearch Relevance rule indexing failed. See details in exception log.")
            );
        }
    }

    public function getProducts(array $productIds): iterable
    {
        $this->searchCriteriaBuilder->addFilter('entity_id', $productIds, 'in');
        $searchCriteria = $this->searchCriteriaBuilder->create();

        return $this->productRepository->getList($searchCriteria)->getItems();
    }

    public function reindexFull(): void
    {
        try {
            $this->resource->getConnection()->truncateTable($this->getIndexTable());

            foreach ($this->getActiveRules() as $rule) {
                $this->doReindex($rule);
            }
        } catch (\Exception $e) {
            $this->critical($e);

            throw new LocalizedException(
                __("Relevance rule indexing failed. See details in exception log.")
            );
        }
    }

    public function reindexByRuleIds(array $ids): void
    {
        $table = $this->getIndexTable();
        $relevanceRuleCollection = $this->relevanceRuleCollectionFactory->create();
        $relevanceRuleCollection->addFieldToFilter(RelevanceRuleInterface::RULE_ID, $ids);
        $connection = $this->resource->getConnection();
        $connection->delete(
            $table,
            [
                $connection->prepareSqlCondition(RelevanceRuleInterface::RULE_ID, ['in' => $ids])
            ]
        );

        try {
            foreach ($relevanceRuleCollection as $relevanceRule) {
                try {
                    $this->doReindex($relevanceRule);
                } catch (NoSuchEntityException $e) {
                    null;// do nothing
                }
            }
        } catch (\Exception $e) {
            $this->critical($e);

            throw new LocalizedException(
                __("Relevance rule indexing failed. See details in exception log.")
            );
        }
    }

    private function doReindex(RelevanceRuleInterface $rule): void
    {
        if ($rule->isConditionEmpty()) {
            return;
        }

        $rows = [];
        $size = 0;
        $productIds = $rule->getCatalogRule()->getMatchingProductIds();

        foreach ($productIds as $productId => $validationByWebsite) {
            if (empty($validationByWebsite[$rule->getWebsiteId()])) {
                continue;
            }

            $rows[] = $this->generateIndexData($rule, $productId);
            $size++;

            if ($size == $this->batchCount) {
                $this->resource->getConnection()->insertMultiple($this->getIndexTable(), $rows);
                $rows = [];
                $size = 0;
            }
        }

        if (!empty($rows)) {
            $this->resource->getConnection()->insertMultiple($this->getIndexTable(), $rows);
        }
    }

    private function applyRule(RelevanceRuleInterface $rule, ProductInterface $product): IndexBuilder
    {
        $table = $this->getIndexTable();
        $connection = $this->resource->getConnection();
        $connection->delete(
            $table,
            [
                $connection->prepareSqlCondition(RelevanceRuleInterface::RULE_ID, ['eq' => $rule->getId()]),
                $connection->prepareSqlCondition(self::PRODUCT_ID, ['eq' => $product->getId()])
            ]
        );

        if ($rule->isConditionEmpty() || !$rule->getCatalogRule()->validate($product)) {
            return $this;
        }

        try {
            $rows = [$this->generateIndexData($rule, $product->getId())];
            $this->resource->getConnection()->insertMultiple($table, $rows);
        } catch (\Exception $e) {
            throw $e;
        }

        return $this;
    }

    private function generateIndexData(RelevanceRuleInterface $rule, int $productId): array
    {
        return [
            RelevanceRuleInterface::RULE_ID => $rule->getId(),
            RelevanceRuleInterface::WEBSITE_ID => $rule->getWebsiteId(),
            RelevanceRuleInterface::MULTIPLIER => $rule->getMultiplier(),
            self::PRODUCT_ID => $productId
        ];
    }

    private function cleanByIds(array $productIds): void
    {
        $select = $this->resource->getConnection()
            ->select()
            ->from($this->getIndexTable(), self::PRODUCT_ID)
            ->distinct()
            ->where(self::PRODUCT_ID . ' IN (?)', $productIds);
        $query = $this->resource->getConnection()->deleteFromSelect($select, $this->getIndexTable());

        $this->resource->getConnection()->query($query);
    }

    private function getIndexTable(): string
    {
        return $this->resource->getTableName(self::TABLE_NAME);
    }

    private function getActiveRules(): iterable
    {
        return $this->ruleRepository->getActiveRules();
    }

    private function critical(\Exception $exception): void
    {
        $this->logger->critical($exception);
    }
}
