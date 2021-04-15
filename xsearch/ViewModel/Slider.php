<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\ViewModel;

use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\Slider\IsCanRenderInterface;
use Amasty\Xsearch\Model\Slider\SliderProductsProviderInterface;
use Amasty\Xsearch\ViewModel\ProductList\GetAddToCartParams;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Block\Product\ReviewRendererInterface;
use Magento\Catalog\Helper\Product\Compare;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Pricing\Render;
use Magento\Framework\Pricing\Render as PriceRender;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Wishlist\Helper\Data;

class Slider implements ArgumentInterface
{
    const IMAGE_ID = 'amasty_xsearch_slider_image';

    /**
     * @var IsCanRenderInterface
     */
    private $isCanRender;

    /**
     * @var SliderProductsProviderInterface
     */
    private $sliderProductsProvider;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var ReviewRendererInterface
     */
    private $reviewRenderer;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var PriceRender
     */
    private $priceRenderer;

    /**
     * @var Data
     */
    private $wishlistHelper;

    /**
     * @var Compare
     */
    private $compareHelper;

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var GetAddToCartParams
     */
    private $getAddToCartParams;

    /**
     * @var string
     */
    private $blockType;

    public function __construct(
        IsCanRenderInterface $isCanRender,
        SliderProductsProviderInterface $sliderProductsProvider,
        ImageFactory $imageFactory,
        ReviewRendererInterface $reviewRenderer,
        LayoutInterface $layout,
        Data $wishlistHelper,
        Compare $compareHelper,
        UrlHelper $urlHelper,
        RedirectInterface $redirect,
        Config $config,
        FormKey $formKey,
        GetAddToCartParams $getAddToCartParams,
        string $blockType = ''
    ) {
        $this->isCanRender = $isCanRender;
        $this->sliderProductsProvider = $sliderProductsProvider;
        $this->imageFactory = $imageFactory;
        $this->reviewRenderer = $reviewRenderer;
        $this->layout = $layout;
        $this->wishlistHelper = $wishlistHelper;
        $this->compareHelper = $compareHelper;
        $this->urlHelper = $urlHelper;
        $this->redirect = $redirect;
        $this->config = $config;
        $this->formKey = $formKey;
        $this->getAddToCartParams = $getAddToCartParams;
        $this->blockType = $blockType;
    }

    public function isCanRender(): bool
    {
        return $this->isCanRender->execute();
    }

    public function getProducts(): iterable
    {
        return $this->sliderProductsProvider->getProducts();
    }

    public function getImageHtml(Product $product): ?string
    {
        return $this->imageFactory->create($product, self::IMAGE_ID, [])->toHtml();
    }

    public function getSliderTitle(): ?string
    {
        return $this->config->getModuleConfig("{$this->blockType}/title");
    }

    public function getReviewsHtml(Product $product): string
    {
        return $this->reviewRenderer->getReviewsSummaryHtml($product, ReviewRendererInterface::SHORT_VIEW);
    }

    private function getPriceRenderer(): PriceRender
    {
        if (!$this->priceRenderer) {
            $this->priceRenderer = $this->layout->createBlock(
                PriceRender::class,
                '',
                ['data' => [
                    'price_render_handle' => 'catalog_product_prices',
                    'include_container' => true,
                    'display_minimal_price' => true,
                    'zone' => Render::ZONE_ITEM_LIST,
                    'list_category_page' => true
                ]]
            );
        }

        return $this->priceRenderer;
    }

    public function getProductPriceHtml(Product $product): string
    {
        return $this->getPriceRenderer()->render(FinalPrice::PRICE_CODE, $product);
    }

    public function isWishlistAllowed(): bool
    {
        return $this->wishlistHelper->isAllow();
    }

    public function getAddToWishlistPostParams(Product $product): string
    {
        return $this->wishlistHelper->getAddParams($product);
    }

    public function getAddToCompareParams(Product $product): string
    {
        $postParams = $this->compareHelper->getPostDataParams($product);
        $currentUenc = $this->urlHelper->getEncodedUrl();
        $newUenc = $this->urlHelper->getEncodedUrl($this->redirect->getRefererUrl());

        return str_replace($currentUenc, $newUenc, $postParams);
    }

    public function isRedirectToCart(): bool
    {
        return $this->config->isRedirectToCartEnabled();
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    public function getAddToCartUrl(Product $product): string
    {
        return $this->getAddToCartParams->getAddToCartUrl($product);
    }

    public function getAddToCartPostParams(Product $product): array
    {
        return $this->getAddToCartParams->getAddToCartPostParams($product);
    }
}
