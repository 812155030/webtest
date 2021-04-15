<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\ViewModel;

use Amasty\Xsearch\Model\Config;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class FormMiniData implements ArgumentInterface
{
    /**
     * @var Data
     */
    private $urlHelper;

    /**
     * @var Config
     */
    private $moduleConfigProvider;

    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        Data $urlHelper,
        SerializerInterface $jsonSerializer,
        Config $moduleConfigProvider,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        $this->urlHelper = $urlHelper;
        $this->moduleConfigProvider = $moduleConfigProvider;
        $this->jsonSerializer = $jsonSerializer;
        $this->urlBuilder = $urlBuilder;
    }

    public function getOptions(?string $url = null): string
    {
        return $this->jsonSerializer->serialize([
            'url' => $this->urlBuilder->getUrl('amasty_xsearch/autocomplete/index'),
            'isDynamicWidth' => $this->moduleConfigProvider->isDynamicWidth(),
            'isProductBlockEnabled' => $this->moduleConfigProvider->isProductBlockEnabled(),
            'width' => $this->moduleConfigProvider->getPopupWidth(),
            'minChars' => $this->moduleConfigProvider->getMinChars(),
            'currentUrlEncoded' => $this->getCurrentUrlEncoded($url)
        ]);
    }

    public function getCurrentUrlEncoded(?string $url): string
    {
        return $this->urlHelper->getEncodedUrl($url);
    }
}
