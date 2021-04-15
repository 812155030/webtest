<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Ui\DataProvider;

use Amasty\Xsearch\Model\ResourceModel\UserSearch\Grid\Activity\Collection;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as MagentoDataProvider;

class Activity extends MagentoDataProvider
{
    /**
     * @var DateTime
     */
    private $dateTime;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        DateTime $dateTime,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->dateTime = $dateTime;
    }

    protected function searchResultToOutput(SearchResultInterface $searchResult): array
    {
        $result = [];

        /** @var DataObject $aggregatedItem **/
        foreach ($searchResult->getItems() as $aggregatedItem) {
            if ($aggregatedItem->hasData(Collection::GROUP_PERIOD)) {
                $aggregatedItem->setData(
                    Collection::GROUP_PERIOD,
                    $this->dateTime->date('d F Y', $aggregatedItem->getData(Collection::GROUP_PERIOD))
                );
            }

            $result['items'][] = $aggregatedItem->getData();
        }

        $result['totalRecords'] = $searchResult->getTotalCount();

        return $result;
    }
}
