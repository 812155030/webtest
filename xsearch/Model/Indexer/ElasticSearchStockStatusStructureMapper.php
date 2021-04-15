<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Indexer;

class ElasticSearchStockStatusStructureMapper
{
    const STOCK_STATUS = 'stock_status';
    const TYPE_INTEGER = 'integer';

    /**
     * @return array
     */
    public function buildEntityFields(): array
    {
        return [self::STOCK_STATUS => ['type' => self::TYPE_INTEGER]];
    }
}
