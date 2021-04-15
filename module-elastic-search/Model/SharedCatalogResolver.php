<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model;

use Amasty\ElasticSearch\Model\Search\Adapter;
use Amasty\ElasticSearch\Model\ResourceModel\SharedCatalog;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class SharedCatalogResolver
{
    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @var \Magento\SharedCatalog\Model\Config
     */
    private $sharedConfig;

    /**
     * @var \Magento\Company\Model\CompanyContext
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
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        SharedCatalog $sharedCatalog
    ) {
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->sharedCatalog = $sharedCatalog;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEnabled()
    {
        $customerGroupId = $this->getCompanyContext()->getCustomerGroupId();
        $website = $this->storeManager->getWebsite()->getId();

        return $this->getSharedConfig()->isActive(ScopeInterface::SCOPE_WEBSITE, $website)
            && !$this->getCustomerGroupManagement()->isMasterCatalogAvailable($customerGroupId);
    }

    /**
     * @param array $searchResponse
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resolve($searchResponse = [])
    {
        $customerGroupId = $this->getCompanyContext()->getCustomerGroupId();
        $correctIds = $this->sharedCatalog->getCatalogItems($customerGroupId);

        $searchResponse[Adapter::PRODUCTS] = array_intersect_key(
            $searchResponse[Adapter::PRODUCTS],
            array_flip($correctIds)
        );
        $searchResponse['hits'] = count($searchResponse[Adapter::PRODUCTS]);

        return $searchResponse;
    }

    /**
     * @return \Magento\SharedCatalog\Model\CustomerGroupManagement
     */
    public function getCustomerGroupManagement()
    {
        if (!$this->customerGroupManagement) {
            $this->customerGroupManagement = $this->objectManager->get(
                \Magento\SharedCatalog\Model\CustomerGroupManagement::class
            );
        }

        return $this->customerGroupManagement;
    }

    /**
     * @return \Magento\SharedCatalog\Model\Config
     */
    protected function getSharedConfig()
    {
        if (!$this->sharedConfig) {
            $this->sharedConfig = $this->objectManager->get(\Magento\SharedCatalog\Model\Config::class);
        }

        return $this->sharedConfig;
    }

    /**
     * @return \Magento\Company\Model\CompanyContext
     */
    protected function getCompanyContext()
    {
        if (!$this->companyContext) {
            $this->companyContext = $this->objectManager->get(\Magento\Company\Model\CompanyContext::class);
        }

        return $this->companyContext;
    }
}
