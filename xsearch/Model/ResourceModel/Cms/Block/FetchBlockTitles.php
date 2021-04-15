<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\ResourceModel\Cms\Block;

use Magento\Cms\Model\Block;
use Magento\Cms\Model\ResourceModel\Block\Collection;

class FetchBlockTitles
{
    /**
     * @var Collection
     */
    private $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function execute(): array
    {
        $this->collection
            ->addFieldToSelect([Block::TITLE])
            ->addFieldToFilter(Block::IS_ACTIVE, Block::STATUS_ENABLED);

        return $this->collection->getConnection()->fetchPairs(
            $this->collection->getSelect(),
            [Block::BLOCK_ID => Block::TITLE]
        );
    }
}
