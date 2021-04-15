<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\System\Config\Source;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\ResourceModel\Page\Collection as CmsCollection;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\Store;

class PagesToExclude implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CmsCollection
     */
    private $collection;

    private $request;

    public function __construct(
        CollectionFactory $collectionFactory,
        RequestInterface $request
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->request = $request;
    }

    private function getCollection(): CmsCollection
    {
        if ($this->collection === null) {
            $this->collection = $this->collectionFactory->create();
            $this->collection->addFieldToFilter(PageInterface::IS_ACTIVE, 1);
            $storeId = $this->request->getParam('store', false);
            $storeId = $storeId === false ? Store::DEFAULT_STORE_ID : (int)$storeId;
            $this->collection->addStoreFilter($storeId);
        }

        return $this->collection;
    }

    public function toOptionArray(): array
    {
        return array_map(function (PageInterface $cmsPage) {
            return [
                'label' => $cmsPage->getTitle(),
                'value' => $cmsPage->getIdentifier()
            ];
        }, $this->getCollection()->getItems());
    }
}
