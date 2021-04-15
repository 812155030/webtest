<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Model\System\Config\Backend;

class Weight extends \Magento\Framework\App\Config\Value
{

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $mathRandom;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var \Amasty\Xsearch\Helper\Data
     */
    private $xSearchHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Attribute
     */
    private $attributeResource;

    /**
     * @var \Amasty\Base\Model\Serializer
     */
    private $serializer;

    /**
     * Weight constructor.
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository
     * @param \Amasty\Xsearch\Helper\Data $xSearchHelper
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Catalog\Model\ResourceModel\Attribute $attributeResource
     * @param \Amasty\Base\Model\Serializer $serializer
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository,
        \Amasty\Xsearch\Helper\Data $xSearchHelper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Catalog\Model\ResourceModel\Attribute $attributeResource,
        \Amasty\Base\Model\Serializer $serializer,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->mathRandom = $mathRandom;
        $this->attributeRepository = $attributeRepository;
        $this->xSearchHelper = $xSearchHelper;
        $this->attributeResource = $attributeResource;
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Prepare data before save
     *
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $result = [];

        if (is_array($value)) {
            foreach ($value as $data) {
                if (!$data
                    || !is_array($data)
                    || !(isset($data['weight']) && isset($data['attributes_weight']))
                ) {
                    continue;
                }

                $result[$data['attributes_weight']] = $data['weight'];
            }
        } else {
            $result = $this->serializer->unserialize($value);
        }

        if ($result && is_array($result)) {
            $this->setWeightAndSearchable($result);
            $this->deactivateSearchable($result);
            $this->setValue($this->serializer->serialize($result));
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterLoad()
    {
        $value = $this->encodeArrayFieldValue($this->getActiveInSearchAttributes());
        $this->setValue($value);

        return $this;
    }

    /**
     * @param array $value
     * @return array
     */
    private function encodeArrayFieldValue(array $value)
    {
        $result = [];
        foreach ($value as $attributes => $weight) {
            $resultId = $this->mathRandom->getUniqueHash('_');
            $result[$resultId] = ['attributes' => $attributes, 'weight' => $weight];
        }

        return $result;
    }

    /**
     * Get attributes in wich is_searchable true
     * @return array
     */
    private function getActiveInSearchAttributes()
    {
        $values = [];

        $productAttributes = $this->xSearchHelper->getProductAttributes();
        if (!$productAttributes) {
            return $values;
        }

        foreach ($productAttributes as $attribute) {
            if ($attribute->getIsSearchable()) {
                $values[$attribute->getAttributeCode()] = $attribute->getSearchWeight();
            }
        }

        return $values;
    }

    private function setWeightAndSearchable(array $result): void
    {
        foreach ($result as $attributeCode => $weight) {
            $attribute = $this->attributeRepository->get($attributeCode);
            $attribute->setSearchWeight($weight);
            $attribute->setIsSearchable(true);

            /* saving with resource model, because magento repository on version less 2.1.8 break attribute options*/
            $this->attributeResource->save($attribute);
        }
    }

    /**
     * Set in the attribute is_searchable in false
     * @param array $values
     * @return void
     */
    private function deactivateSearchable(array $values): void
    {
        $productAttributes = $this->xSearchHelper->getProductAttributes('is_searchable');

        if (!$productAttributes) {
            return;
        }

        $productAttributes = array_flip($productAttributes);

        foreach ($values as $attributeCode => $weight) {
            unset($productAttributes[$attributeCode]);
        }

        foreach ($productAttributes as $attribute => $value) {
            $attribute = $this->attributeRepository->get($attribute);
            $attribute->setIsSearchable(false);
            $this->attributeResource->save($attribute);
        }
    }
}
