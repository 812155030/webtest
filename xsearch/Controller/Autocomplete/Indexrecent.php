<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Controller\Autocomplete;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\DesignLoader;

class Indexrecent implements HttpGetActionInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var DesignLoader
     */
    private $designLoader;

    public function __construct(
        ResultFactory $resultFactory,
        RequestInterface $request,
        DesignLoader $designLoader
    ) {
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->designLoader = $designLoader;
    }

    public function execute(): ?ResultInterface
    {
        $this->designLoader->load();
        $result = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);

        if (!$this->request->isAjax()) {
            $result->setStatusHeader(403, '1.1', 'Forbidden');
        }

        return $result;
    }
}
