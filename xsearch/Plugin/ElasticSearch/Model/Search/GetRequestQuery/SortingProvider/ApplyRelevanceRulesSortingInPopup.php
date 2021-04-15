<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Plugin\ElasticSearch\Model\Search\GetRequestQuery\SortingProvider;

use Amasty\ElasticSearch\Model\Search\GetRequestQuery\SortingProvider;
use Amasty\Xsearch\Model\Config as ModuleConfig;
use Magento\Framework\App\RequestInterface as HttpRequest;

class ApplyRelevanceRulesSortingInPopup
{
    const XSEARCH_MODULE_NAME = 'amasty_xsearch';

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var HttpRequest
     */
    private $request;

    public function __construct(
        ModuleConfig $config,
        HttpRequest $request
    ) {
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param SortingProvider $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsCanApplyRelevanceSorting(SortingProvider $subject, bool $result): bool
    {
        if ($this->isCanApply() && !$this->config->isApplyRelevanceRulesInPopup()) {
            $result = false;
        }

        return $result;
    }

    private function isCanApply(): bool
    {
        return $this->request->getModuleName() === self::XSEARCH_MODULE_NAME;
    }
}
