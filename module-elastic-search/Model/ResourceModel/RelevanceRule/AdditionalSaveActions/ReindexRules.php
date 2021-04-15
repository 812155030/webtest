<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


declare(strict_types=1);

namespace Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule\AdditionalSaveActions;

use Amasty\ElasticSearch\Api\Data\RelevanceRuleInterface;
use Amasty\ElasticSearch\Model\Indexer\RelevanceRule\RuleProductProcessor;

class ReindexRules implements CRUDCallbackInterface
{
    /**
     * @var RuleProductProcessor
     */
    private $ruleProductProcessor;

    public function __construct(
        RuleProductProcessor $ruleProductProcessor
    ) {
        $this->ruleProductProcessor = $ruleProductProcessor;
    }

    public function execute(RelevanceRuleInterface $rule): void
    {
        if (!$this->ruleProductProcessor->isIndexerScheduled()) {
            $this->ruleProductProcessor->reindexRow($rule->getId());
        }
    }
}
