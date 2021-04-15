<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\ViewModel\Preload;

use Amasty\Xsearch\Block\Search\AbstractSearch;
use Amasty\Xsearch\ViewModel\ConfigurableBlockRenderer;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Sidebar implements ArgumentInterface
{
    /**
     * @var ConfigurableBlockRenderer
     */
    private $blocksProvider;

    public function __construct(
        ConfigurableBlockRenderer $blocksProvider
    ) {
        $this->blocksProvider = $blocksProvider;
    }

    public function getSidebarBlocksHtml(): string
    {
        $result = '';

        /** @var AbstractSearch $block **/
        foreach ($this->blocksProvider->getBlocks() as $block) {
            if (count($block->getResults())) {
                $result .= $block->toHtml();
            }
        }

        return $result;
    }
}
