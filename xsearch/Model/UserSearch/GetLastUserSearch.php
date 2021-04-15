<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\UserSearch;

use Amasty\Xsearch\Model\Authentication;
use Amasty\Xsearch\Model\ResourceModel\UserSearch\Collection as UserSearchCollection;
use Amasty\Xsearch\Model\ResourceModel\UserSearch\CollectionFactory;
use Amasty\Xsearch\Model\UserSearch as UserSearchModel;
use Magento\Framework\Exception\NoSuchEntityException;

class GetLastUserSearch
{
    /**
     * @var UserSearchModel[]
     */
    private $cache = [];

    /**
     * @var CollectionFactory
     */
    private $userSearchCollectionFactory;

    /**
     * @var Authentication
     */
    private $authentication;

    public function __construct(
        CollectionFactory $userSearchCollectionFactory,
        Authentication $authentication
    ) {
        $this->userSearchCollectionFactory = $userSearchCollectionFactory;
        $this->authentication = $authentication;
    }

    /**
     * Get last user search query
     *
     * @return UserSearchModel
     * @throws NoSuchEntityException
     */
    public function execute(): UserSearchModel
    {
        $customerId = $this->authentication->getCustomerIdentifier();

        if (!isset($this->cache[$customerId])) {
            /** @var UserSearchCollection $userSearchCollection **/
            $userSearchCollection = $this->userSearchCollectionFactory->create();
            $userSearchCollection->addFieldToFilter('user_key', $customerId);
            $userSearchCollection->setOrder('id');
            $userSearchCollection->setLimit(1);
            $userInfoSearch = $userSearchCollection->getFirstItem();

            if (!$userInfoSearch->getId()) {
                throw new NoSuchEntityException(__('User Search was not found'));
            }

            $this->cache[$customerId] = $userInfoSearch;
        }

        return $this->cache[$customerId];
    }
}
