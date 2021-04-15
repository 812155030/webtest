<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\ResourceModel\Slider;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RecentlyViewed extends AbstractDb
{
    const MAIN_TABLE = 'amasty_xsearch_frontend_product_actions';
    const ACTION_TYPE = 1;

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, 'id');
    }

    public function applyFilterToProductCollection(
        string $customerIdentifier,
        int $storeId,
        ProductCollection $collection
    ): void {
        $select = $collection->getSelect();
        $select->join(
            ['axfpa' => $this->getMainTable()],
            sprintf(
                'axfpa.product_id = e.%1$s and axfpa.type_id = %2$d and axfpa.store_id = %3$d '
                . 'and axfpa.customer_identifier = \'%4$s\'',
                $collection->getIdFieldName(),
                self::ACTION_TYPE,
                $storeId,
                $customerIdentifier
            ),
            []
        );
        $select->order('axfpa.happened_at DESC');
    }

    public function appendNewRecentlyViewedProduct(string $customerIdentifier, int $productId, int $storeId): void
    {
        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            [
                'type_id' => self::ACTION_TYPE,
                'customer_identifier' => $customerIdentifier,
                'product_id' => $productId,
                'store_id' => $storeId,
                'happened_at' => new \Zend_Db_Expr('NOW()')
            ]
        );
    }

    public function deleteOutdatedRecentProductsRowIds(int $storeId, int $recentViewedLifeTime): void
    {
        $select = $this->getOutdatedRowsSelect($storeId, $recentViewedLifeTime);
        $connection = $this->getConnection();
        $ids = $connection->fetchCol($select);

        if (!empty($ids)) {
            $connection->delete($this->getMainTable(), ['id in (?)' => $ids]);
        }
    }

    private function getSelect(): Select
    {
        $select = $this->getConnection()->select();
        $select->from(['main_table' => $this->getMainTable()]);
        $select->where('type_id = ?', self::ACTION_TYPE);

        return $select;
    }

    private function getOutdatedRowsSelect(int $storeId, int $recentViewedLifeTime): Select
    {
        $select = $this->getSelect();
        $select->where('store_id = ?', $storeId);
        $select->where(
            new \Zend_Db_Expr("date_add(happened_at, interval {$recentViewedLifeTime} second) < now()")
        );
        $select->reset(Select::COLUMNS);
        $select->columns(['id']);

        return $select;
    }
}
