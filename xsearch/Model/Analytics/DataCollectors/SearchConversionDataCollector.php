<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Analytics\DataCollectors;

use Amasty\Xsearch\Model\ResourceModel\UserSearch as UserSearchResource;
use Amasty\Xsearch\Model\UserSearch\GetLastUserSearch;
use Magento\Framework\Exception\NoSuchEntityException;

class SearchConversionDataCollector implements AnalyticsDataCollectorInterface
{
    const IDENTIFIER = 'search_click';

    /**
     * @var GetLastUserSearch
     */
    private $getLastUserSearch;

    /**
     * @var UserSearchResource
     */
    private $userSearchResource;

    public function __construct(
        GetLastUserSearch $getLastUserSearch,
        UserSearchResource $userSearchResource
    ) {
        $this->getLastUserSearch = $getLastUserSearch;
        $this->userSearchResource = $userSearchResource;
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
        try {
            $userSearch = $this->getLastUserSearch->execute();
            $userSearch->setProductClick(1);
            $this->userSearchResource->save($userSearch);
        } catch (NoSuchEntityException $e) {
            return; //No action required
        }
    }
}
