<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\Indexer;

class ExternalIndexerProvider
{
    /**
     * @var array
     */
    private $sources;

    public function __construct(
        array $sources = []
    ) {
        $this->sources = $sources;
    }

    /**
     * @param int $storeId
     * @param int $pageNum
     * @param int $batchSize
     * @return array
     */
    public function getDocuments($storeId, $pageNum, $batchSize)
    {
        $documents = [];
        foreach ($this->sources as $indexType => $source) {
            $documents[$indexType] = $source->get($storeId, $pageNum, $batchSize);
        }

        return $documents;
    }

    /**
     * @return array
     */
    public function getIndexTypes()
    {
        return array_keys($this->sources);
    }
}
