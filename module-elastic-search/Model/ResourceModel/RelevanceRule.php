<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\ResourceModel;

use Amasty\ElasticSearch\Api\Data\RelevanceRuleInterface;
use Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule\AdditionalSaveActions\CRUDCallbackInterface;
use Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule\CRUDCallbackPull as OnDeleteCallbackPull;
use Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule\CRUDCallbackPull as OnSaveCallbackPull;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class RelevanceRule extends AbstractDb
{
    /**
     * @var OnSaveCallbackPull
     */
    private $onDeleteCallbackPull;
    /**
     * @var OnSaveCallbackPull
     */
    private $onSaveCallbackPull;

    public function __construct(
        Context $context,
        OnDeleteCallbackPull $onDeleteCallbackPull,
        OnSaveCallbackPull $onSaveCallbackPull,
        $connectionName = null
    ) {
        $this->onDeleteCallbackPull = $onDeleteCallbackPull;
        $this->onSaveCallbackPull = $onSaveCallbackPull;

        parent::__construct($context, $connectionName);
    }

    protected function _construct()
    {
        $this->_init(RelevanceRuleInterface::TABLE_NAME, RelevanceRuleInterface::RULE_ID);
    }

    protected function _afterDelete(AbstractModel $object): RelevanceRule
    {
        /**
         * @var RelevanceRuleInterface $object
         * @var CRUDCallbackInterface $callback
         */
        foreach ($this->onDeleteCallbackPull as $callback) {
            $callback->execute($object);
        }

        return parent::_afterDelete($object);
    }

    protected function _afterSave(AbstractModel $object): RelevanceRule
    {
        /**
         * @var RelevanceRuleInterface $object
         * @var CRUDCallbackInterface $callback
         */
        foreach ($this->onSaveCallbackPull as $callback) {
            $callback->execute($object);
        }

        return parent::_afterSave($object);
    }
}
