<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\ResourceModel\UserSearch\Grid\Activity;

use Amasty\Xsearch\Model\ResourceModel\UserSearch;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Zend_Db_Expr;

class Collection extends SearchResult
{
    const UNIQ_QUERIES = 'unique_query';
    const TOTAL_QUERIES = 'popularity';
    const UNIQ_USERS_AMOUNT = 'unique_user';
    const UNIQ_CLICKS_BY_USER = 'uniq_clicks_by_user';
    const ENGAGEMENT = 'product_click';
    const GROUP_PERIOD = 'group_period';
    const CREATED_AT = 'created_at';

    protected function _construct()
    {
        $this->_init(DataObject::class, UserSearch::class);
        $this->setMainTable(UserSearch::MAIN_TABLE);
    }

    protected function _renderOrders(): Collection
    {
        if (empty($this->_orders)) {
            $this->_orders[self::GROUP_PERIOD] = Select::SQL_DESC;
        }

        return parent::_renderOrders();
    }

    protected function _initSelect(): void
    {
        $select = $this->getConnection()->select();
        $select = $select->from(['subquery_table' => $this->getMainTable()], []);
        $select->columns([
            self::UNIQ_QUERIES => new Zend_Db_Expr('COUNT(DISTINCT subquery_table.query_id)'),
            self::TOTAL_QUERIES => new Zend_Db_Expr('COUNT(subquery_table.query_id)'),
            self::UNIQ_USERS_AMOUNT => new Zend_Db_Expr('COUNT(DISTINCT subquery_table.user_key)'),
            self::UNIQ_CLICKS_BY_USER => new Zend_Db_Expr('uniq_searches.product_click_amount'),
            self::ENGAGEMENT => new Zend_Db_Expr(
                'ROUND(uniq_searches.product_click_amount / COUNT(DISTINCT user_key) * 100, 2)'
            ),
            self::GROUP_PERIOD => $this->getGroupPeriodExpression()
        ]);
        $select->joinInner(
            ['uniq_searches' => $this->getClicksByUserSelect()],
            sprintf('%s = uniq_searches.period', (string)$this->getGroupPeriodExpression()),
            []
        );
        $select->group([
            self::GROUP_PERIOD,
            'uniq_searches.period'
        ]);

        $this->getSelect()->from(['main_table' => $select]);
    }

    private function getClicksByUserSelect(): Select
    {
        $select = $this->getConnection()->select();
        $select->from(['axus' => $this->getMainTable()]);
        $select->reset(Select::COLUMNS);
        $select->columns([
            'product_click_amount' => new Zend_Db_Expr('COUNT(DISTINCT axus.user_key)'),
            'period' => $this->getGroupPeriodExpression('axus')
        ]);
        $select->where($this->_getConditionSql('axus.product_click', ['notnull' => true]));
        $select->group('period');

        return $select;
    }

    private function getGroupPeriodExpression(string $alias = ''): Zend_Db_Expr
    {
        $alias = $alias ? "{$alias}." : $alias;

        return new \Zend_Db_Expr(sprintf('DATE(%screated_at)', $alias));
    }
}
