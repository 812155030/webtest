<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\Indexer\Structure;

use Amasty\ElasticSearch\Api\Data\Indexer\Structure\AnalyzerBuilderInterface;
use Amasty\ElasticSearch\Model\Indexer\Structure\AnalyzerBuilder\DefaultBuilder;
use Amasty\ElasticSearch\Model\Source\CustomAnalyzer;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class AnalyserBuilder
 */
class AnalyserBuilderFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CustomAnalyzersMetaInfoProvider
     */
    private $customAnalyzersMetaInfoProvider;

    public function __construct(
        ObjectManagerInterface $objectManager,
        CustomAnalyzersMetaInfoProvider $customAnalyzersMetaInfoProvider
    ) {
        $this->objectManager = $objectManager;
        $this->customAnalyzersMetaInfoProvider = $customAnalyzersMetaInfoProvider;
    }

    /**
     * @param array $data
     * @return AnalyzerBuilderInterface
     */
    public function create($type): AnalyzerBuilderInterface
    {
        return $this->objectManager->create($this->getBuilderInstanceName($type));
    }

    /**
     * @param string $type
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getBuilderInstanceName($type): string
    {
        return $type === CustomAnalyzer::DISABLED
            ? DefaultBuilder::class : $this->customAnalyzersMetaInfoProvider->getAnalyzerClass($type);
    }
}
