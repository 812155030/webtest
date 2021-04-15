<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Block;

use Amasty\Xsearch\Helper\Data as Helper;

class Jsinit extends \Magento\Framework\View\Element\Template
{
    const XML_PATH_TEMPLATE_DYNAMIC_WIDTH = 'general/dynamic_search_width';
    const XML_PATH_TEMPLATE_WIDTH = 'general/popup_width';
    const XML_PATH_TEMPLATE_MIN_CHARS = 'general/min_chars';

    const XML_PATH_RECENT_SEARCHES_FIRST_CLICK = 'recent_searches/first_click';
    const XML_PATH_POPULAR_SEARCHES_FIRST_CLICK = 'popular_searches/first_click';

    const DEFAULT_WIDTH = 900;

    /**
     * @var \Amasty\Xsearch\Block\Search\Recent
     */
    private $recentSearch;

    /**
     * @var \Amasty\Xsearch\Block\Search\Popular
     */
    private $popularSearch;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    private $urlHelper;
    
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    private $jsonEncoder;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        Helper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->urlHelper = $urlHelper;
        $this->helper = $helper;
        $this->jsonEncoder = $jsonEncoder;
    }

    /**
     * @param null $url
     * @return string
     */
    public function getOptions($url = null)
    {
        return $this->jsonEncoder->encode([
            'url' => $this->getUrl('amasty_xsearch/autocomplete/index'),
            'isDynamicWidth' => $this->isDynamicWidth(),
            'width' => $this->getWidth(),
            'minChars' => $this->getMinChars(),
            'currentUrlEncoded' => $this->getCurrentUrlEncoded($url)
        ]);
    }

    /**
     * @return mixed
     */
    public function isDynamicWidth()
    {
        return $this->helper->getModuleConfig(self::XML_PATH_TEMPLATE_DYNAMIC_WIDTH);
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        $width = $this->helper->getModuleConfig(self::XML_PATH_TEMPLATE_WIDTH);
        if (!$width) {
            $width = self::DEFAULT_WIDTH;
        }
        return $width;
    }

    public function getMinChars()
    {
        $min = (int)$this->helper->getModuleConfig(self::XML_PATH_TEMPLATE_MIN_CHARS);
        return max(1, $min);
    }

    /**
     * @param null $url
     * @return string
     */
    public function getCurrentUrlEncoded($url = null)
    {
        return $this->urlHelper->getEncodedUrl($url);
    }

    /**
     * @return bool
     */
    public function isShowRecentPreload()
    {
        return $this->getShowRecentByFirstClick()
            && count($this->getRecentSearch()->getResults());
    }

    /**
     * @return bool
     */
    public function isShowPopularPreload()
    {
        return $this->getShowPopularByFirstClick()
            && count($this->getPopularSearch()->getResults());
    }

    /**
     * @return string
     */
    public function getPreload()
    {
        $recentHtml = '';
        $popularHtml = '';
        if ($this->isShowRecentPreload()) {
            $recentHtml .= $this->getRecentSearch()->toHtml();
        }

        if ($this->isShowPopularPreload()) {
            $popularHtml .= $this->getPopularSearch()->toHtml();
        }

        $recentPos = $this->helper->getModuleConfig(Helper::XML_PATH_TEMPLATE_RECENT_SEARCHES_POSITION);
        $popularPos = $this->helper->getModuleConfig(Helper::XML_PATH_TEMPLATE_POPULAR_SEARCHES_POSITION);
        if ($recentPos < $popularPos) {
            return $recentHtml . $popularHtml;
        }

        return $popularHtml . $recentHtml;
    }

    /**
     * @return \Amasty\Xsearch\Block\Search\Recent
     */
    private function getRecentSearch()
    {
        if (!$this->recentSearch) {
            $this->recentSearch = $this->_layout
                ->createBlock(\Amasty\Xsearch\Block\Search\Recent::class, 'amasty.xsearch.search.recent');
        }

        return $this->recentSearch;
    }

    /**
     * @return \Amasty\Xsearch\Block\Search\Popular
     */
    private function getPopularSearch()
    {
        if (!$this->popularSearch) {
            $this->popularSearch = $this->_layout
                ->createBlock(\Amasty\Xsearch\Block\Search\Popular::class, 'amasty.xsearch.search.popular');
        }

        return $this->popularSearch;
    }

    /**
     * @return bool
     */
    private function getShowRecentByFirstClick()
    {
        return (bool) $this->helper->getModuleConfig(self::XML_PATH_RECENT_SEARCHES_FIRST_CLICK);
    }

    /**
     * @return bool
     */
    private function getShowPopularByFirstClick()
    {
        return (bool) $this->helper->getModuleConfig(self::XML_PATH_POPULAR_SEARCHES_FIRST_CLICK);
    }
}
