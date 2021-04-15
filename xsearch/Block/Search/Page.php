<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Block\Search;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Page extends AbstractSearch
{
    const CATEGORY_BLOCK_PAGE = 'page';

    /**
     * @return string
     */
    public function getBlockType()
    {
        return self::CATEGORY_BLOCK_PAGE;
    }

    /**
     * @inheritdoc
     */
    protected function generateCollection()
    {
        $collection = parent::generateCollection();
        $collection->addSearchFilter($this->getQuery()->getQueryText());
        $collection->addStoreFilter($this->_storeManager->getStore());
        $collection->addFieldToFilter(PageInterface::IS_ACTIVE, 1);
        $collection->setPageSize($this->getLimit());
        $this->addExcludePagesCondition($collection);

        return $collection;
    }

    private function addExcludePagesCondition(AbstractCollection $collection): void
    {
        $excludedIdentifiers = $this->configProvider->getExcludedCmsPagesIdentifiers();

        if (!empty($excludedIdentifiers)) {
            $collection->addFieldToFilter(PageInterface::IDENTIFIER, ['nin' => $excludedIdentifiers]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getName(\Magento\Framework\DataObject $item)
    {
        return $this->generateName($item->getTitle());
    }

    /**
     * @param \Magento\Framework\DataObject $page
     * @return string
     * phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    public function getDescription(\Magento\Framework\DataObject $page)
    {
        $content = preg_replace(
            '|<style[^>]*?>(.*?)</style>|si',
            '',
            html_entity_decode($page->getContent())
        );
        $content = preg_replace(
            '|<script[^>]*?>(.*?)</script>|si',
            '',
            html_entity_decode($content)
        );
        $descStripped = $this->stripTags(html_entity_decode($content), null, true);
        $this->replaceVariables($descStripped);

        //phpcs:enable Magento2.Functions.DiscouragedFunction
        return $this->getHighlightText($descStripped);
    }
}
