<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\Source;

use Amasty\ElasticSearch\Model\Indexer\Structure\CustomAnalyzersMetaInfoProvider;

class CustomAnalyzer implements \Magento\Framework\Data\OptionSourceInterface
{
    const DISABLED = 'disabled';
    const CHINESE = 'smartcn';
    const JAPANESE = 'kuromoji';
    const KOREAN = 'nori';

    /**
     * @var CustomAnalyzersMetaInfoProvider
     */
    private $metaInfoProvider;

    public function __construct(
        CustomAnalyzersMetaInfoProvider $customAnalyzersMetaInfoProvider
    ) {
        $this->metaInfoProvider = $customAnalyzersMetaInfoProvider;
    }

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        $customAnalyzers = array_map(function ($alias) {
            return [
                'value' => $alias,
                'label' => $this->metaInfoProvider->getAnalyzerLabel($alias)
            ];
        }, $this->metaInfoProvider->getAllAnalyzers());
        array_unshift($customAnalyzers, ['value' => self::DISABLED, 'label' => __('Disabled')]);

        return $customAnalyzers;
    }
}
