<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model;

use Amasty\ElasticSearch\Api\Data\RelevanceRuleInterface;
use Amasty\ElasticSearch\Api\RelevanceRuleRepositoryInterface;
use Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule\BoostMultipliersProvider;
use Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule\CollectionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class RelevanceRuleRepository implements RelevanceRuleRepositoryInterface
{
    /**
     * @var RelevanceRuleFactory
     */
    private $ruleFactory;

    /**
     * @var ResourceModel\RelevanceRule
     */
    private $ruleResource;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var BoostMultipliersProvider
     */
    private $boostMultipliersProvider;

    public function __construct(
        RelevanceRuleFactory $ruleFactory,
        ResourceModel\RelevanceRule $ruleResource,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate,
        BoostMultipliersProvider $boostMultipliersProvider
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->ruleResource = $ruleResource;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->localeDate = $localeDate;
        $this->boostMultipliersProvider = $boostMultipliersProvider;
    }

    /**
     * @inheritdoc
     */
    public function save(RelevanceRuleInterface $rule)
    {
        try {
            if ($rule->getId()) {
                $rule = $this->get($rule->getId())->addData($rule->getData());
            }

            $this->ruleResource->save($rule);
        } catch (AlreadyExistsException $e) {
            throw new AlreadyExistsException(__($e->getMessage()));
        } catch (\Exception $e) {
            if ($rule->getStopWordId()) {
                throw new CouldNotSaveException(
                    __('Unable to save stopWord with ID %1. Error: %2', [$rule->Id(), $e->getMessage()])
                );
            }

            throw new CouldNotSaveException(__('Unable to save new Relevance Rule. Error: %1', $e->getMessage()));
        }

        return $rule;
    }

    /**
     * @inheritdoc
     */
    public function get($ruleId = null)
    {
        /** @var \Amasty\ElasticSearch\Model\RelevanceRule $rule */
        $rule = $this->ruleFactory->create();
        if ($ruleId !== null) {
            $this->ruleResource->load($rule, $ruleId);
            if (!$rule->getId()) {
                throw new NoSuchEntityException(__('Relevance Rule with specified ID "%1" not found.', $ruleId));
            }
        }

        return $rule;
    }

    /**
     * @inheritdoc
     */
    public function delete(RelevanceRuleInterface $rule)
    {
        try {
            $this->ruleResource->delete($rule);
        } catch (\Exception $e) {
            if ($rule->getId()) {
                throw new CouldNotDeleteException(
                    __('Unable to remove Relevance Rule with ID %1. Error: %2', [$rule->getId(), $e->getMessage()])
                );
            }

            throw new CouldNotDeleteException(__('Unable to remove Relevance Rule. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($ruleId)
    {
        $stopWordModel = $this->get($ruleId);
        $this->delete($stopWordModel);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getActiveRules()
    {
        return $this->collectionFactory->create()->addActiveFilter();
    }

    /**
     * @param int[] $productIds
     * @param int|null $websiteId
     * @return float[]
     * @throws LocalizedException
     */
    public function getProductBoostMultipliers(?array $productIds = null, ?int $websiteId = null): array
    {
        $websiteId = $websiteId === null ? (int) $this->storeManager->getWebsite()->getId() : $websiteId;

        return $this->boostMultipliersProvider->getBoostMultipliers($websiteId, $productIds);
    }
}
