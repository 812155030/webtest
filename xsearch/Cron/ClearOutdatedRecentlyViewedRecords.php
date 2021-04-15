<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Cron;

use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\ResourceModel\Slider\RecentlyViewed as RecentlyViewedResource;
use Magento\Store\Model\StoreManagerInterface;

class ClearOutdatedRecentlyViewedRecords
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var RecentlyViewedResource
     */
    private $recentlyViewedResource;

    public function __construct(
        RecentlyViewedResource $recentlyViewedResource,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->recentlyViewedResource = $recentlyViewedResource;
    }

    /**
     * Fix of gradual degradation of recently viewed
     * products processing and database overflow
     */
    public function execute()
    {
        if ($this->config->isRecentlyViewedEnabled()) {
            foreach ($this->storeManager->getStores() as $store) {
                $storeId = (int)$store->getId();
                $this->recentlyViewedResource->deleteOutdatedRecentProductsRowIds(
                    $storeId,
                    $this->config->getCookieLifeTime($storeId)
                );
            }
        }
    }
}
