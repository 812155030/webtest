<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Indexer;

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

    public function getDocuments(int $storeId): array
    {
        $documents = [];
        foreach ($this->sources as $indexType => $source) {
            $documents[$indexType] = $source->get($storeId);
        }

        return $documents;
    }

    public function getIndexTypes(): array
    {
        return array_keys($this->sources);
    }
}
