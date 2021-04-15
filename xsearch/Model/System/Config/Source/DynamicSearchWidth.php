<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Model\System\Config\Source;

use \Magento\Framework\Option\ArrayInterface;

class DynamicSearchWidth implements ArrayInterface
{
    const DEFAULT_WIDTH = 0;
    const DYNAMIC_WIDTH = 1;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            self::DYNAMIC_WIDTH => __('Dynamic (based on popup width)'),
            self::DEFAULT_WIDTH => __('Default')
        ];

        return $options;
    }
}
