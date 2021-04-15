<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Block\Widget;

use Amasty\Xsearch\Block\Jsinit;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Psr\Log\LoggerInterface;
use Magento\Search\Helper\Data as Helper;

class Search extends Template implements BlockInterface
{
    protected $_template = 'Amasty_Xsearch::widget/search.phtml';

    /**
     * @var Template\Context
     */
    private $context;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Helper
     */
    private $helper;

    public function __construct(
        LoggerInterface $logger,
        Template\Context $context,
        Helper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->context = $context;
        $this->logger = $logger;
        $this->helper = $helper;
    }

    public function getHelper(): Helper
    {
        return $this->helper;
    }
}
