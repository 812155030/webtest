<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Analytics\DataCollectors;

use Amasty\Xsearch\Model\Authentication;
use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\ResourceModel\Slider\RecentlyViewed;
use Magento\Framework\App\Http\Context;
use Magento\Store\Model\StoreManagerInterface;

class ProductViewDataCollector implements AnalyticsDataCollectorInterface
{
    const IDENTIFIER = 'product_view';
    const PRODUCT_ID_KEY = 'product_id';

    /**
     * @var Context
     */
    private $context;

    /**
     * @var RecentlyViewed
     */
    private $recentlyViewed;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Context $context,
        RecentlyViewed $recentlyViewed,
        Config $config,
        Authentication $authentication,
        StoreManagerInterface $storeManager
    ) {
        $this->context = $context;
        $this->recentlyViewed = $recentlyViewed;
        $this->config = $config;
        $this->authentication = $authentication;
        $this->storeManager = $storeManager;
    }

    /**
     * @return string[]
     */
    public function getIdentifiers(): array
    {
        return [self::IDENTIFIER];
    }

    public function collect(array $data): void
    {
        if (isset($data[self::PRODUCT_ID_KEY])
            && $this->config->isRecentlyViewedEnabled()
        ) {
            $customerIdentifier = $this->authentication->getCustomerIdentifier();
            $storeId = (int)$this->storeManager->getStore()->getId();

            $this->recentlyViewed->appendNewRecentlyViewedProduct(
                $customerIdentifier,
                (int)$data[self::PRODUCT_ID_KEY],
                $storeId
            );
        }
    }
}
