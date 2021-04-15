<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Plugin\Search\Model\Query;

use Amasty\Xsearch\Block\Search\BrowsingHistory;
use Magento\Framework\App\CacheInterface;
use Magento\Search\Model\Query;
use Magento\Customer\Model\Session;

class ClearBrowsingHistoryCache
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var CacheInterface
     */
    private $cacheManager;

    /**
     * @var BrowsingHistory
     */
    private $browsingHistoryBlock;

    public function __construct(
        Session $customerSession,
        CacheInterface $cacheManager,
        BrowsingHistory $browsingHistoryBlock
    ) {
        $this->customerSession = $customerSession;
        $this->cacheManager = $cacheManager;
        $this->browsingHistoryBlock = $browsingHistoryBlock;
    }

    public function afterSaveIncrementalPopularity(Query $subject, Query $result): Query
    {
        if ($this->customerSession->getCustomerId()) {
            $this->cacheManager->clean([$this->browsingHistoryBlock->getCustomerIdentity()]);
        }

        return $result;
    }
}
