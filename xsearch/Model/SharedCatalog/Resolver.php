<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Model\SharedCatalog;

use Amasty\Base\Model\MagentoVersion;
use Amasty\Xsearch\Model\ResourceModel\SharedCatalog;
use Amasty\Xsearch\Model\Di\Wrapper as SharedCatalogDiProxy;
use Magento\Company\Model\CompanyContext;
use Magento\SharedCatalog\Model\CustomerGroupManagement;
use Magento\SharedCatalog\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Resolver
{
    /**
     * @var CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @var Config
     */
    private $sharedConfig;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SharedCatalog
     */
    private $sharedCatalog;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    public function __construct(
        StoreManagerInterface $storeManager,
        SharedCatalogDiProxy $customerGroupManagement,
        SharedCatalogDiProxy $sharedConfig,
        SharedCatalogDiProxy $companyContext,
        MagentoVersion $magentoVersion,
        SharedCatalog $sharedCatalog
    ) {
        $this->storeManager = $storeManager;
        $this->sharedCatalog = $sharedCatalog;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->sharedConfig = $sharedConfig;
        $this->magentoVersion = $magentoVersion;
        $this->companyContext = $companyContext;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEnabled(): bool
    {
        $customerGroupId = $this->companyContext->getCustomerGroupId();
        $website = $this->storeManager->getWebsite()->getId();
        $isCatalogAvailable = $this->isCatalogAvailable((int)$customerGroupId);

        return $this->sharedConfig->isActive(ScopeInterface::SCOPE_WEBSITE, $website)
            && !$isCatalogAvailable;
    }

    /**
     * @param int $customerGroupId
     *
     * @return bool
     */
    public function isCatalogAvailable(int $customerGroupId): bool
    {
        if (version_compare($this->magentoVersion->get(), '2.4.2', '>=')) {
            return $this->customerGroupManagement->isPrimaryCatalogAvailable($customerGroupId);
        } else {
            return $this->customerGroupManagement->isMasterCatalogAvailable($customerGroupId);
        }
    }

    /**
     * @param array $searchResponse
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resolve($searchResponse = [])
    {
        $customerGroupId = $this->companyContext->getCustomerGroupId();
        $correctIds = $this->sharedCatalog->getCatalogItems($customerGroupId);

        $searchResponse['products'] = array_intersect_key(
            $searchResponse['products'],
            array_flip($correctIds)
        );
        $searchResponse['hits'] = count($searchResponse['products']);

        return $searchResponse;
    }
}
