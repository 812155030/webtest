<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\Indexer\Structure\DynamicTemplate;

/**
 * Class CategoryPosition
 */
class CategoryPosition
{
    /**
     * @param $storeId
     * @return array
     */
    public function map($storeId)
    {
        return [
            'match' => 'category_position_*',
            'match_mapping_type' => 'string',
            'mapping' => [
                'type' => 'integer',
                'index' => false,
            ]
        ];
    }
}
