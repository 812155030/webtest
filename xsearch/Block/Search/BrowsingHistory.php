<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Block\Search;

use Amasty\Xsearch\Helper\Data as Helper;
use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\ResourceModel\BrowsingHistoryQueryCollectionApplier;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Search\Model\ResourceModel\Query\Collection as QueryCollection;
use Magento\Framework\View\Element\Template\Context;
use Amasty\Xsearch\Model\Search\SearchAdapterResolver;
use Magento\Customer\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Search\Model\QueryFactory;

class BrowsingHistory extends AbstractSearch implements IdentityInterface
{
    const BROWSING_HISTORY_BLOCK_TYPE = 'browsing_history';

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var BrowsingHistoryQueryCollectionApplier
     */
    private $applier;

    public function __construct(
        Context $context,
        Helper $xSearchHelper,
        StringUtils $string,
        QueryFactory $queryFactory,
        Registry $coreRegistry,
        Config $configProvider,
        BrowsingHistoryQueryCollectionApplier $applier,
        Session $customerSession,
        SearchAdapterResolver $searchAdapterResolver,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $xSearchHelper,
            $string,
            $queryFactory,
            $coreRegistry,
            $configProvider,
            $searchAdapterResolver,
            $data
        );
        $this->applier = $applier;
        $this->customerSession = $customerSession;
    }

    /**
     * @return string
     */
    public function getBlockType()
    {
        return self::BROWSING_HISTORY_BLOCK_TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getResults()
    {
        $result = parent::getResults();

        return $this->isNeedUpdate($result) ? $this->updateData($result) : $result;
    }

    private function isNeedUpdate(array $results): bool
    {
        $item = array_shift($results);

        return is_array($item) && !isset($item['num_results']);
    }

    private function updateData(array $results): array
    {
        foreach ($this->getSearchCollection() as $index => $item) {
            $result[$index]['num_results'] = $item->getNumResults();
        }

        return $results;
    }

    /**
     * @return QueryCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generateCollection()
    {
        /**
         * @var QueryCollection $collection
         */
        $collection = parent::generateCollection();
        $this->applier->execute($collection, (int)$this->getCustomerId());

        $collection->setPageSize($this->getLimit());

        return $collection;
    }

    private function getCustomerId(): int
    {
        return (int)$this->customerSession->getCustomerId();
    }

    /**
     * @param \Magento\Framework\DataObject $item
     * @return string
     */
    public function getName(\Magento\Framework\DataObject $item)
    {
        return $this->generateName($item->getQueryText());
    }

    /**
     * @return bool
     */
    public function isNoFollow()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        $identities = parent::getIdentities();
        if ($this->getCustomerId()) {
            $identities[] = $this->getCustomerIdentity();
        }

        return $identities;
    }

    public function getCustomerIdentity(): string
    {
        return self::DEFAULT_CACHE_TAG . '_' . $this->getBlockType() . '_' . $this->getCustomerId();
    }

    /**
     * @return array
     */
    public function getCacheKeyInfo()
    {
        if (!$this->getCustomerId()) {
            return parent::getCacheKeyInfo();
        }

        $cacheKey = Template::getCacheKeyInfo();

        return array_merge(
            [$this->getBlockType(), $this->getCustomerId()],
            $cacheKey
        );
    }
}
