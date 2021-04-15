<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Slider;

use Magento\Catalog\Model\Product;

interface SliderProductsProviderInterface
{
    /**
     * @return Product[]
     */
    public function getProducts(): iterable;
}
