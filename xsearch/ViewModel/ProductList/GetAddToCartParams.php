<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\ViewModel\ProductList;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Url\Helper\Data as CoreUrlHelper;

class GetAddToCartParams
{
    /**
     * @var Cart
     */
    private $cartHelper;

    /**
     * @var CoreUrlHelper
     */
    private $urlHelper;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    public function __construct(
        Cart $cartHelper,
        CoreUrlHelper $urlHelper,
        RedirectInterface $redirect
    ) {
        $this->cartHelper = $cartHelper;
        $this->urlHelper = $urlHelper;
        $this->redirect = $redirect;
    }

    public function getAddToCartUrl(Product $product): string
    {
        $configs = [
            '_escape' => false
        ];

        if (!$product->getTypeInstance()->isPossibleBuyFromList($product)) {
            $configs['_query'] = [
                'options' => 'cart'
            ];

            return $product->getUrlModel()->getUrl($product, $configs);
        }

        return $this->cartHelper->getAddUrl($product, $configs);
    }

    public function getAddToCartPostParams(Product $product): array
    {
        $addToCartUrl = $this->getAddToCartUrl($product);

        return [
            'action' => $addToCartUrl,
            'data' => [
                'product' => (int)$product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getEncodedUrl($addToCartUrl),
                'return_url' => $this->redirect->getRefererUrl()
            ]
        ];
    }
}
