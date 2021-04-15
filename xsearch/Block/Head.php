<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Block;

use Amasty\Xsearch\Helper\Data as Helper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Head extends Template
{
    const XML_PATH_LAYOUT_ENABLED = 'layout/enabled';

    const XML_PATH_LAYOUT_BORDER = 'layout/border';

    const XML_PATH_LAYOUT_HOVER = 'layout/hover';

    const XML_PATH_LAYOUT_HIGHLIGHT = 'layout/highlight';

    const XML_PATH_LAYOUT_BACKGROUND = 'layout/background';

    const XML_PATH_LAYOUT_TEXT = 'layout/text';

    const XML_PATH_LAYOUT_HOVER_TEXT = 'layout/hover_text';
    
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var \Amasty\Base\Model\MagentoVersion
     */
    private $magentoVersion;

    public function __construct(
        Context $context,
        Helper $helper,
        \Amasty\Base\Model\MagentoVersion $magentoVersion,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * @return string
     */
    public function getOriginalFilePath()
    {
        if (version_compare($this->getMagentoVersion(), '2.2.7', '>=')) {
            return 'Magento_Search/js/form-mini';
        } else {
            return 'Magento_Search/form-mini';
        }
    }

    /**
     * @return string
     */
    protected function getMagentoVersion()
    {
        return str_replace(['dev-', '-develop'], '', $this->magentoVersion->get());
    }

    public function getLayoutEnabled(): bool
    {
        return (bool)$this->helper->getModuleConfig(self::XML_PATH_LAYOUT_ENABLED);
    }

    public function getLayoutBorder(): string
    {
        return (string)$this->helper->getModuleConfig(self::XML_PATH_LAYOUT_BORDER);
    }

    public function getLayoutHover(): string
    {
        return (string)$this->helper->getModuleConfig(self::XML_PATH_LAYOUT_HOVER);
    }

    public function getLayoutHighlight(): string
    {
        return (string)$this->helper->getModuleConfig(self::XML_PATH_LAYOUT_HIGHLIGHT);
    }

    public function getLayoutBackground(): string
    {
        return (string)$this->helper->getModuleConfig(self::XML_PATH_LAYOUT_BACKGROUND);
    }

    public function getLayoutText(): string
    {
        return (string)$this->helper->getModuleConfig(self::XML_PATH_LAYOUT_TEXT);
    }

    public function getLayoutHoverText(): string
    {
        return (string)$this->helper->getModuleConfig(self::XML_PATH_LAYOUT_HOVER_TEXT);
    }
}
