<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Controller\Autocomplete;

use Magento\Framework\App\Action\Context;

class Options extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->urlBuilder = $urlBuilder;
    }

    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            return null;
        }

        $layout = $this->layoutFactory->create();
        $resultJson = $this->resultJsonFactory->create();
        $productsBlock = $layout->createBlock(\Amasty\Xsearch\Block\Jsinit::class, 'amasty.xsearch.product');
        $options = $productsBlock->getOptions($this->urlBuilder->getUrl());

        return $resultJson->setData($options);
    }
}
