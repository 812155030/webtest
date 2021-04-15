<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\ResourceModel\UserSearch\Grid\MostWanted;

use Amasty\Xsearch\Model\ResourceModel\UserSearch;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    const QUERY_TEXT = 'query_text';
    const TOTAL_SEARCHES = 'total_searches';
    const USERS_AMOUNT = 'users_amount';
    const ENGAGEMENT = 'engagement';

    const USER_ENGAGEMENT_TABLE = 'user_engagement';
    const SEARCH_QUERY_TABLE = 'search_query';

    protected function _construct()
    {
        $this->_init(DataObject::class, UserSearch::class);
        $this->setMainTable(UserSearch::MAIN_TABLE);
    }

    protected function _renderOrders(): Collection
    {
        if (empty($this->_orders)) {
            $this->_orders[self::TOTAL_SEARCHES] = Select::SQL_DESC;
        }

        return parent::_renderOrders();
    }

    protected function _initSelect(): void
    {
        $select = $this->getConnection()->select();
        $select->from(['subquery_table' => $this->getMainTable()], []);
        $select->columns([
            self::QUERY_TEXT => sprintf('ps.%s', self::QUERY_TEXT),
            self::TOTAL_SEARCHES => new \Zend_Db_Expr('COUNT(subquery_table.query_id)'),
            self::USERS_AMOUNT => new \Zend_Db_Expr('COUNT(DISTINCT subquery_table.user_key)'),
            self::ENGAGEMENT => sprintf('%s.%s', self::USER_ENGAGEMENT_TABLE, self::ENGAGEMENT)
        ]);
        $select->joinLeft(
            ['ps' => $this->getTable(self::SEARCH_QUERY_TABLE)],
            'subquery_table.query_id = ps.query_id',
            []
        );
        $select->joinInner(
            [self::USER_ENGAGEMENT_TABLE => $this->getEngagementSelect()],
            sprintf('subquery_table.query_id = %s.query_id', self::USER_ENGAGEMENT_TABLE),
            []
        );
        $select->where($this->_getConditionSql(sprintf('ps.%s', self::QUERY_TEXT), ['notnull' => true]));
        $select->group('subquery_table.query_id');
        $this->getSelect()->from(['main_table' => $select]);
    }

    private function getEngagementSelect(): Select
    {
        $select = $this->getConnection()->select();
        $select->from(['sbct' => $this->getSearchesByUserSelect()]);
        $select->reset(Select::COLUMNS);
        $select->columns([
            'query_id' => 'sbct.query_id',
            self::ENGAGEMENT => new \Zend_Db_Expr('ROUND(sum(is_clicked) / count(query_id) * 100, 2)')
        ]);
        $select->group('sbct.query_id');

        return $select;
    }

    private function getSearchesByUserSelect(): Select
    {
        $select = $this->getConnection()->select();
        $select->from(['sbu' => $this->getMainTable()]);
        $select->reset(Select::COLUMNS);
        $select->columns([
           'query_id',
           'is_clicked' => new \Zend_Db_Expr('if(count(sbu.product_click) > 0, 1, 0)')
        ]);
        $select->group(['query_id', 'user_key']);

        return $select;
    }
}
