<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Controller\Adminhtml\RelevanceRule;

use Amasty\ElasticSearch\Model\RelevanceRuleFactory;
use Amasty\ElasticSearch\Model\RelevanceRuleRepository;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

abstract class AbstractRelevance extends Action
{
    const ADMIN_RESOURCE = 'Amasty_ElasticSearch::relevance_rules';
    const CURRENT_RULE = 'amasty_elastic_relevance_rule';

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var RelevanceRuleRepository
     */
    protected $ruleRepository;

    /**
     * @var RelevanceRuleFactory
     */
    protected $ruleFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        RelevanceRuleRepository $ruleRepository,
        RelevanceRuleFactory $ruleFactory,
        Registry $registry,
        TimezoneInterface $timezone,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
        $this->ruleRepository = $ruleRepository;
        $this->ruleFactory = $ruleFactory;
        $this->registry = $registry;
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Amasty_ElasticSearch::Amasty_ElasticSearch');
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Relevance Rules'));
        return $resultPage;
    }
}
