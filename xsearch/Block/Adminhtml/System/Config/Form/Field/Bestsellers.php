<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Block\Adminhtml\System\Config\Form\Field;

class Bestsellers extends AbstractField
{
    const MODULE_NAME = 'Amasty_Sorting';
    const CONFIG_MODULE_NAME = 'bestsellers';

    protected function getNote()
    {
        return __(
            'Increase the convenience of searching for products by customers with the '
            . 'help of various sorting options provided by Improved Sorting plugin.'
        );
    }

    protected function getStatus()
    {
        return __('Amasty Sorting Not Installed');
    }
}
