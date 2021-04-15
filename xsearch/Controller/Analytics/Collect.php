<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Controller\Analytics;

use Amasty\Xsearch\Model\Analytics\AnalyticsDataCollectionProcessor;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory as EmptyResponseFactory;
use Magento\Framework\Controller\ResultInterface;

class Collect implements HttpPostActionInterface
{
    const TELEMETRY_PARAMETER = 'telemetry';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var AnalyticsDataCollectionProcessor
     */
    private $analyticsDataCollectionProcessor;

    /**
     * @var EmptyResponseFactory
     */
    private $resultFactory;

    public function __construct(
        RequestInterface $request,
        AnalyticsDataCollectionProcessor $analyticsDataCollectionProcessor,
        EmptyResponseFactory $resultFactory
    ) {
        $this->request = $request;
        $this->analyticsDataCollectionProcessor = $analyticsDataCollectionProcessor;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Collect analytics data
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute(): ResultInterface
    {
        if (!$this->isRequestValid()) {
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
        } else {
            $telemetry = $this->request->getParam(self::TELEMETRY_PARAMETER, []);

            if (!empty($telemetry)) {
                $this->analyticsDataCollectionProcessor->process($telemetry);
            }
        }

        return $this->getResponse();
    }

    private function getResponse(): ResultInterface
    {
        return $this->resultFactory->create(EmptyResponseFactory::TYPE_RAW);
    }

    private function isRequestValid(): bool
    {
        return $this->request->isAjax();
    }
}
