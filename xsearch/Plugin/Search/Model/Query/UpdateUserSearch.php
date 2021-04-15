<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Plugin\Search\Model\Query;

use Magento\Search\Model\Query;
use Amasty\Xsearch\Model\UserSearchFactory;
use Magento\Customer\Model\Session;

class UpdateUserSearch
{
    /**
     * @var UserSearchFactory
     */
    private $userSearch;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Query
     */
    private $query;

    public function __construct(
        UserSearchFactory $userSearch,
        Session $customerSession,
        Query $query
    ) {
        $this->userSearch = $userSearch;
        $this->customerSession = $customerSession;
        $this->query = $query;
    }

    public function afterSaveIncrementalPopularity(Query $subject, Query $result): Query
    {
        $customerId = $this->customerSession->getCustomerId() ?: $this->customerSession->getSessionId();
        $query = $this->query->loadByQueryText($subject->getQueryText());
        if ($query->getQueryId()) {
            $this->userSearch->create()->setUserKey($customerId)
                ->setQueryId($query->getQueryId())
                ->save();
        }

        return $result;
    }
}
