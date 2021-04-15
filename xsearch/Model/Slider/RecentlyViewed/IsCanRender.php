<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Slider\RecentlyViewed;

use Amasty\Xsearch\Model\Authentication;
use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\Slider\IsCanRenderInterface;

class IsCanRender implements IsCanRenderInterface
{
    /**
     * @var ProductsProvider
     */
    private $productsProvider;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Authentication
     */
    private $authentication;

    public function __construct(
        ProductsProvider $productsProvider,
        Authentication $authentication,
        Config $config
    ) {
        $this->productsProvider = $productsProvider;
        $this->config = $config;
        $this->authentication = $authentication;
    }

    public function execute(): bool
    {
        return $this->config->isRecentlyViewedEnabled()
            && $this->isSliderHasProducts();
    }

    private function isSliderHasProducts(): bool
    {
        return count($this->productsProvider->getProducts()) > 0;
    }
}
