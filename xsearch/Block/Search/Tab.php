<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Block\Search;

use Magento\Framework\View\Element\Template;

class Tab extends \Magento\Framework\View\Element\Template
{
    /**
     * @var array
     */
    private $tabs = [];

    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * @var \Amasty\Xsearch\Helper\Data
     */
    private $helper;

    public function __construct(
        Template\Context $context,
        \Magento\Framework\Module\Manager $moduleManager,
        \Amasty\Xsearch\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleManager = $moduleManager;
        $this->helper = $helper;
    }

    /**
     * @param string $tabName
     * @param string $blockName
     * @param string $blockClass
     * @param string $template
     * @param string $tabType
     * @return void
     */
    public function addTab($tabName, $blockName, $blockClass, $template, $tabType): void
    {
        if (class_exists($blockClass)
            && !(strpos($blockClass, 'Landing') !== false
                && !$this->moduleManager->isEnabled('Amasty_Xlanding'))
        ) {
            $tabName = $this->helper->getTabTitle($tabType) ?: $tabName;
            $this->tabs[] = [
                'name' => $tabName,
                'block_name' => $blockName,
                'block_class' => $blockClass,
                'template' => $template
            ];
        }
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTabs()
    {
        foreach ($this->tabs as $index => $tab) {
            $block = $this->getLayout()->createBlock($tab['block_class'], $tab['block_name']);
            $html = $block ? $block->setTemplate($tab['template'])->toHtml() : '';
            $itemsCount = $block ? count($block->getResults()) : 0;

            $this->tabs[$index]['html'] = $html;
            $this->tabs[$index]['items_count'] = $itemsCount;
        }

        return $this->tabs;
    }

    /**
     * @return bool
     */
    public function isTabsEnabled()
    {
        return (bool)$this->helper->getModuleConfig('general/enable_tabs_search_result');
    }

    /**
     * @return string
     */
    public function getProductCount()
    {
        $block = $this->getChildBlock('search.result');
        if ($block) {
            $count = $block->getResultCount();
        }

        return $count ?? '';
    }
}
