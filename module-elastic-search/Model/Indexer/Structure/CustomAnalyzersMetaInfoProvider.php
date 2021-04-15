<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


declare(strict_types=1);

namespace Amasty\ElasticSearch\Model\Indexer\Structure;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Endpoint to extend the module by adding a custom analyzer
 *
 * @api
 */
class CustomAnalyzersMetaInfoProvider
{
    /**
     * @var array[]
     */
    private $customAnalyzersMetaInfo = [];

    public function __construct(
        array $customAnalyzersMetaInfo = []
    ) {
        $this->customAnalyzersMetaInfo = $customAnalyzersMetaInfo;
    }

    /**
     * @return array
     */
    public function getAllAnalyzers(): array
    {
        return array_keys($this->customAnalyzersMetaInfo);
    }

    /**
     * @param string $analyzerAlias
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAnalyzerClass(string $analyzerAlias): string
    {
        if (!isset($this->customAnalyzersMetaInfo[$analyzerAlias]['class'])) {
            throw new NoSuchEntityException(__('Mapping not found for custom analyzer alias %1', $analyzerAlias));
        }

        return $this->customAnalyzersMetaInfo[$analyzerAlias]['class'];
    }

    /**
     * @param string $analyzerAlias
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAnalyzerLabel(string $analyzerAlias): string
    {
        if (!isset($this->customAnalyzersMetaInfo[$analyzerAlias]['label'])) {
            throw new NoSuchEntityException(
                __('Invalid mapping for custom analyzer %1. Label not found.', $analyzerAlias)
            );
        }

        return $this->customAnalyzersMetaInfo[$analyzerAlias]['label'];
    }
}
