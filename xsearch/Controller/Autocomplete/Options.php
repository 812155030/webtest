<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Controller\Autocomplete;

use Amasty\Xsearch\ViewModel\FormMiniData;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\UrlInterface;

class Options implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var FormMiniData
     */
    private $formMiniData;

    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder,
        JsonFactory $resultJsonFactory,
        FormMiniData $formMiniData
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->formMiniData = $formMiniData;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        if (!$this->request->isAjax()) {
            $resultJson->setStatusHeader(403, '1.1', 'Forbidden');
        } else {
            $resultJson->setData($this->formMiniData->getOptions($this->urlBuilder->getUrl()));
        }

        return $resultJson;
    }
}
