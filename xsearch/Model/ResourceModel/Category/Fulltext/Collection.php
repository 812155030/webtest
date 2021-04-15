<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Model\ResourceModel\Category\Fulltext;

use Magento\Framework\Search\Response\QueryResponse;

class Collection extends \Magento\Catalog\Model\ResourceModel\Category\Collection
{
    /**
     * @var QueryResponse
     */
    protected $queryResponse;

    /**
     * @var string
     */
    private $queryText;

    /**
     * @var \Magento\Framework\Search\Request\Builder
     */
    private $requestBuilder;

    /**
     * @var string
     */
    private $searchRequestName;

    /**
     * @var array
     */
    private $fullTextSpecialChars = ['$', '@', '*', '<', '>', '(', ')', '-', '+', '~', '"', '.'];

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Search\Request\Builder $requestBuilder,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        $searchRequestName = 'amasty_xsearch_category'
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $connection
        );

        $this->requestBuilder = $requestBuilder;
        $this->searchRequestName = $searchRequestName;
    }

    /**
     * @param string $query
     * @return $this
     */
    public function addSearchFilter($query)
    {
        $query = str_replace($this->fullTextSpecialChars, ' ', $query);
        $this->queryText = trim($this->queryText . ' ' . $query);
        return $this;
    }

    protected function _renderFiltersBefore()
    {
        if ($this->queryText) {
            $select = $this->getSelect();
            $select->joinInner(
                ['index_table' => $this->resolveIndexTable()],
                'index_table.entity_id = e.entity_id',
                []
            );

            $queryText = $this->getConnection()->quote($this->queryText . '*');
            $where = 'MATCH(index_table.data_index) AGAINST (' . $queryText . ' IN BOOLEAN MODE)';
            $select->where($where);
            $select->group('e.entity_id');
        }

        parent::_renderFiltersBefore();
    }

    /**
     * @return array[]
     */
    public function getIndexFulltextValues()
    {
        $select = $this->getConnection()->select()
            ->from(
                ['posts_tags' => $this->getTable('amasty_xsearch_category_fulltext_scope') . $this->getStoreId()],
                ['entity_id', 'data_index']
            );
        $items = $this->getConnection()->fetchAll($select);
        $result = [];
        foreach ($items as $item) {
            $value = trim($item['data_index']);
            $id = $item['entity_id'];
            if (!isset($result[$id])) {
                $result[$id] = $value;
            } else {
                $result[$id] .= ' ' . $value;
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    private function resolveIndexTable()
    {
        return $this->getTable('amasty_xsearch_category_fulltext_scope') . $this->getStoreId();
    }
}
