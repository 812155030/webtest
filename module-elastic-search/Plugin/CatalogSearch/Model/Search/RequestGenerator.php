<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Plugin\CatalogSearch\Model\Search;

use Magento\CatalogSearch\Model\Search\RequestGenerator as SearchRequestGenerator;

class RequestGenerator
{
    /**
     * @var \Amasty\Base\Model\MagentoVersion
     */
    private $magentoVersion;

    public function __construct(\Amasty\Base\Model\MagentoVersion $magentoVersion)
    {
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * @param SearchRequestGenerator $subject
     * @param array $requests
     * @return array
     */
    public function afterGenerate(SearchRequestGenerator $subject, $requests)
    {
        //TODO deprecated. remove in next releases
        if (version_compare($this->magentoVersion->get(), '2.2.0', '<')) {
            foreach ($requests as $requestContainer => &$request) {
                if (isset($request['queries'][$requestContainer]['queryReference'])
                    && !empty($request['queries'][$requestContainer]['queryReference'])
                ) {
                    foreach ($request['queries'][$requestContainer]['queryReference'] as &$reference) {
                        $reference['clause'] = 'must';
                    }
                }
            }
        }

        return $requests;
    }
}
