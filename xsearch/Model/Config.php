<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model;

use Amasty\Xsearch\Model\System\Config\Source\RelatedTerms;
use Magento\CatalogSearch\Model\ResourceModel\EngineProvider;
use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const MODULE_SECTION_NAME = 'amasty_xsearch/';
    const PERMANENT_REDIRECT_CODE = 301;
    const TEMPORARY_REDIRECT_CODE = 302;
    const DEFAULT_POPUP_WIDTH = 900;

    const XML_PATH_TEMPLATE_WIDTH = 'general/popup_width';
    const XML_PATH_TEMPLATE_MIN_CHARS = 'general/min_chars';
    const XML_PATH_TEMPLATE_DYNAMIC_WIDTH = 'general/dynamic_search_width';
    const XML_PATH_RECENT_SEARCHES_FIRST_CLICK = 'recent_searches/first_click';
    const XML_PATH_TEMPLATE_RECENT_SEARCHES_ENABLED = 'recent_searches/enabled';
    const XML_PATH_POPULAR_SEARCHES_FIRST_CLICK = 'popular_searches/first_click';
    const XML_PATH_TEMPLATE_POPULAR_SEARCHES_ENABLED = 'popular_searches/enabled';
    const XML_PATH_TEMPLATE_RECENT_SEARCHES_POSITION = 'recent_searches/position';
    const XML_PATH_TEMPLATE_POPULAR_SEARCHES_POSITION = 'popular_searches/position';
    const XML_PATH_BROWSING_HISTORY_FIRST_CLICK = 'browsing_history/first_click';
    const XML_PATH_BROWSING_HISTORY_ENABLED = 'browsing_history/enabled';
    const XML_PATH_BROWSING_HISTORY_POSITION = 'browsing_history/position';
    const XML_PATH_EXCLUDED_CMS_PAGES = 'page/excluded_pages';
    const XML_PATH_TEMPLATE_RECENT_VIEWED_ENABLED = 'recently_viewed/enabled';
    const XML_PATH_TEMPLATE_RECENT_VIEWED_TITLE = 'recently_viewed/title';
    const XML_PATH_TEMPLATE_RECENT_VIEWED_LIMIT = 'recently_viewed/limit';
    const XML_PATH_TEMPLATE_IS_REDIRECT_TO_CART = 'checkout/cart/redirect_to_cart';
    const XML_PATH_EMPTY_RESULT_BLOCK = 'general/empty_result_block';
    const XML_PATH_BESTSELLERS_ENABLED = 'bestsellers/enabled';
    const XML_PATH_BESTSELLERS_TITLE = 'bestsellers/title';
    const XML_PATH_BESTSELLERS_POSITION = 'bestsellers/position';
    const XML_PATH_BESTSELLERS_LIMIT = 'bestsellers/limit';
    const XML_PATH_ENABLE_POPUP_INDEX = 'general/enable_popup_index';
    const XML_PATH_TEMPLATE_OUT_OF_STOCK_LAST = 'product/out_of_stock_last';
    const XML_PATH_AMASTY_ELASTIC_CONNECTION = 'amasty_elastic/connection/';
    const XML_PATH_CATALOG_SEARCH = 'catalog/search/';
    const XML_PATH_TEMPLATE_PRODUCT_ENABLED = 'product/enabled';
    const XML_PATH_TEMPLATE_ENABLE_RELEVANCE_RULES_IN_POPUP = 'product/apply_relevance_rules_in_popup';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string $path
     * @param int $storeId
     * @return mixed
     */
    public function getModuleConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::MODULE_SECTION_NAME . $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getGeneralConfig(string $path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return int
     */
    public function getRedirectType()
    {
        return $this->getModuleConfig('general/four_zero_four_redirect');
    }

    /**
     * @return bool
     */
    public function hasRedirect()
    {
        return (bool)$this->getRedirectType();
    }

    /**
     * @return bool
     */
    public function isPermanentRedirect()
    {
        return $this->getRedirectType() == self::PERMANENT_REDIRECT_CODE;
    }

    /**
     * @return int
     */
    public function getRedirectCode()
    {
        return $this->isPermanentRedirect() ? self::PERMANENT_REDIRECT_CODE : self::TEMPORARY_REDIRECT_CODE;
    }

    /**
     * @param int $searchResultCount
     * @return bool
     */
    public function canShowRelatedTerms($searchResultCount = 0)
    {
        switch ($this->getModuleConfig('general/show_related_terms')) {
            case RelatedTerms::DISABLED:
                return false;
            case RelatedTerms::SHOW_ALWAYS:
                return true;
            case RelatedTerms::SHOW_ONLY_WITHOUT_RESULTS:
                return !$searchResultCount;
        }

        return false;
    }
    /**
     * @return bool
     */
    public function canShowRelatedNumberResults()
    {
        return (bool)$this->getModuleConfig('general/show_related_terms_results');
    }

    public function isShowOutOfStockLast(): bool
    {
        return (bool) $this->getModuleConfig(self::XML_PATH_TEMPLATE_OUT_OF_STOCK_LAST);
    }

    public function getShowRecentByFirstClick(): bool
    {
        return (bool)$this->getModuleConfig(self::XML_PATH_RECENT_SEARCHES_FIRST_CLICK);
    }

    public function getShowPopularByFirstClick(): bool
    {
        return (bool)$this->getModuleConfig(self::XML_PATH_POPULAR_SEARCHES_FIRST_CLICK);
    }

    public function isDynamicWidth(): bool
    {
        return (bool)$this->getModuleConfig(self::XML_PATH_TEMPLATE_DYNAMIC_WIDTH);
    }

    public function getPopupWidth(): int
    {
        return (int)$this->getModuleConfig(self::XML_PATH_TEMPLATE_WIDTH) ?: self::DEFAULT_POPUP_WIDTH;
    }

    public function getMinChars(): int
    {
        $minChars = (int)$this->getModuleConfig(self::XML_PATH_TEMPLATE_MIN_CHARS);

        return max(1, $minChars);
    }

    public function getPosition(string $ConfigPath): int
    {
        $position = (int)$this->getModuleConfig($ConfigPath);

        return max(1, $position);
    }

    public function getRecentSearchesPosition(): int
    {
        return $this->getPosition(self::XML_PATH_TEMPLATE_RECENT_SEARCHES_POSITION);
    }

    public function getPopularSearchesPosition(): int
    {
        return $this->getPosition(self::XML_PATH_TEMPLATE_POPULAR_SEARCHES_POSITION);
    }

    public function isPopularSearchesEnabled(): bool
    {
        return (bool)$this->getModuleConfig(self::XML_PATH_TEMPLATE_POPULAR_SEARCHES_ENABLED);
    }

    public function isBrowsingHistoryEnabled(): bool
    {
        return (bool)$this->getModuleConfig(self::XML_PATH_BROWSING_HISTORY_ENABLED);
    }

    public function isRecentSearchesEnabled(): bool
    {
        return (bool)$this->getModuleConfig(self::XML_PATH_TEMPLATE_RECENT_SEARCHES_ENABLED);
    }

    public function isMysqlEngine(): bool
    {
        return $this->scopeConfig->getValue(EngineProvider::CONFIG_ENGINE_PATH) == 'mysql';
    }

    /**
     * @return string[]
     */
    public function getExcludedCmsPagesIdentifiers(): array
    {
        $identifiers = (string)$this->getModuleConfig(self::XML_PATH_EXCLUDED_CMS_PAGES);
        $identifiers = array_map('trim', explode(',', $identifiers));

        return array_filter($identifiers);
    }

    public function isRecentlyViewedEnabled(): bool
    {
        return (bool)$this->getModuleConfig(self::XML_PATH_TEMPLATE_RECENT_VIEWED_ENABLED);
    }

    public function getRecentlyViewedBlockTitle(): string
    {
        return (string)$this->getModuleConfig(self::XML_PATH_TEMPLATE_RECENT_VIEWED_TITLE);
    }

    public function getRecentlyViewedBlockLimit(?int $storeId = null): int
    {
        return (int)$this->getModuleConfig(self::XML_PATH_TEMPLATE_RECENT_VIEWED_LIMIT, $storeId);
    }

    public function isRedirectToCartEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_TEMPLATE_IS_REDIRECT_TO_CART);
    }

    public function getResultBlockId(): int
    {
        return (int) $this->getModuleConfig(self::XML_PATH_EMPTY_RESULT_BLOCK);
    }

    public function isBestsellersBlockEnabled(): bool
    {
        return (bool)$this->getModuleConfig(self::XML_PATH_BESTSELLERS_ENABLED);
    }

    public function getBestsellersBlockTitle(): string
    {
        return (string)$this->getModuleConfig(self::XML_PATH_BESTSELLERS_TITLE);
    }

    public function getBestsellersBlockPosition(): int
    {
        return (int)$this->getModuleConfig(self::XML_PATH_BESTSELLERS_POSITION);
    }

    public function getBestsellersBlockProductsLimit(): int
    {
        return (int)$this->getModuleConfig(self::XML_PATH_BESTSELLERS_LIMIT);
    }

    public function getCookieLifeTime(int $storeId): int
    {
        return (int)$this->scopeConfig->getValue(Custom::XML_PATH_WEB_COOKIE_COOKIE_LIFETIME);
    }

    public function isEnablePopupIndex(): bool
    {
        return (bool) $this->getModuleConfig(self::XML_PATH_ENABLE_POPUP_INDEX);
    }

    public function getEngine(): string
    {
        return (string) $this->scopeConfig->getValue('catalog/search/engine');
    }

    public function isElasticEngine(): bool
    {
        return strpos($this->getEngine(), 'elastic') !== false;
    }

    public function isAmastyElasticEngine(): bool
    {
        return $this->getEngine() == 'amasty_elastic';
    }

    public function getConnectionData(array $testData = []): array
    {
        $path = $this->getBasepathToConfig();

        $defaultData = [
            'hostname' => $this->scopeConfig->getValue($path . 'server_hostname') ?: 'localhost',
            'port' => $this->scopeConfig->getValue($path . 'server_port') ?: '9200',
            'index' => $this->scopeConfig->getValue($path . 'index_prefix') ?: 'magento2',
            'enableAuth' => $this->scopeConfig->getValue($path . 'enable_auth') ?: 0,
            'username' => $this->scopeConfig->getValue($path . 'username'),
            'password' => $this->scopeConfig->getValue($path . 'password'),
            'timeout' => $this->scopeConfig->getValue($path . 'server_timeout') ?: 15,
        ];

        return array_merge($defaultData, $testData);
    }

    public function getIndexName(string $indexType, int $storeId): string
    {
        if ($indexType == 'catalogsearch_fulltext') {
            $indexType = 'product';
        }
        $path = $this->getBasepathToConfig();

        return $this->scopeConfig->getValue($path . 'index_prefix') . '_' . $indexType . '_' . $storeId;
    }

    private function getBasepathToConfig(): string
    {
        return $this->isAmastyElasticEngine()
            ? self::XML_PATH_AMASTY_ELASTIC_CONNECTION
            : self::XML_PATH_CATALOG_SEARCH . $this->getEngine() . '_';
    }

    public function isProductBlockEnabled(): bool
    {
        return (bool)$this->getModuleConfig(self::XML_PATH_TEMPLATE_PRODUCT_ENABLED);
    }

    public function isApplyRelevanceRulesInPopup(): bool
    {
        return (bool) $this->getModuleConfig(self::XML_PATH_TEMPLATE_ENABLE_RELEVANCE_RULES_IN_POPUP);
    }
}
