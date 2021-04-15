<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Model\ResourceModel\Db\Context;

class ConfigurableResolver extends AbstractDb
{
    const TABLE = 'catalog_product_relation';

    private $linkedField;

    public function __construct(
        Context $context,
        ProductResource $productResource,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->linkedField = $productResource->getLinkField();
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, 'parent_id');
    }

    /**
     * @param array $parentIds
     * @return array
     */
    public function getRelationSkuValues(array $parentIds)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            ['product_relation' => $this->getTable(self::TABLE)],
            []
        )->joinInner(
            ['parent_product_entity' => $this->getTable('catalog_product_entity')],
            'product_relation.parent_id = parent_product_entity.' . $this->linkedField,
            ['parent_product_entity.entity_id']
        )->joinRight(
            ['product_entity' => $this->getTable('catalog_product_entity')],
            'product_entity.entity_id = product_relation.child_id',
            ['GROUP_CONCAT(product_entity.sku)']
        )->where(
            'parent_product_entity.entity_id IN (?)',
            $parentIds
        )->group('product_relation.parent_id');

        return $connection->fetchPairs($select);
    }
}
