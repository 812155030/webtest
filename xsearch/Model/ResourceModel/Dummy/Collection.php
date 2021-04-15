<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Model\ResourceModel\Dummy;

use Magento\Framework\Data\Collection\EntityFactoryInterface;

class Collection extends \Magento\Framework\Data\Collection
{
    public function __construct(EntityFactoryInterface $entityFactory)
    {
        $this->_setIsLoaded(true);
        $this->_items = [];
        parent::__construct($entityFactory);
    }

    /**
     * @return array
     */
    public function getIndexFulltextValues()
    {
        return [];
    }
}
