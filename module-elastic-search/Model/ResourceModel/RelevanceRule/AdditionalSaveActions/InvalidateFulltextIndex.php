<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


declare(strict_types=1);

namespace Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule\AdditionalSaveActions;

use Amasty\ElasticSearch\Api\Data\RelevanceRuleInterface;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Processor;

class InvalidateFulltextIndex implements CRUDCallbackInterface
{
    /**
     * @var Processor
     */
    private $catalogSearchIndexProcessor;

    public function __construct(
        Processor $catalogSearchIndexProcessor
    ) {
        $this->catalogSearchIndexProcessor = $catalogSearchIndexProcessor;
    }

    public function execute(RelevanceRuleInterface $rule): void
    {
        $this->catalogSearchIndexProcessor->markIndexerAsInvalid();
    }
}
