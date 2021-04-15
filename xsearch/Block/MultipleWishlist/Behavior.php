<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Block\MultipleWishlist;

use Amasty\Xsearch\Model\Di\Wrapper;
use Magento\Framework\View\Element\Template;

class Behavior extends Template
{
    /**
     * @var Wrapper
     */
    private $wrapper;

    public function __construct(Template\Context $context, Wrapper $wrapper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->wrapper = $wrapper;
    }

    protected function _toHtml()
    {
        return class_exists(\Magento\MultipleWishlist\Block\Behaviour::class)
            ? $this->wrapper->setTemplate('Magento_MultipleWishlist::behaviour.phtml')->toHtml()
            : '';
    }
}
