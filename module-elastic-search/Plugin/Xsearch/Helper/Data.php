<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Plugin\Xsearch\Helper;

use \Amasty\ElasticSearch\Model\Search\GetRequestQuery\InjectMatchQuery;

class Data
{
    /**
     * @var InjectMatchQuery
     */
    private $injectMatchQuery;

    public function __construct(
        InjectMatchQuery $injectMatchQuery
    ) {
        $this->injectMatchQuery = $injectMatchQuery;
    }

    /**
     * @param \Amasty\Xsearch\Helper\Data $subject
     * @param string $text
     * @param string $query
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeHighlight(
        \Amasty\Xsearch\Helper\Data $subject,
        $text,
        $query
    ) {
        if ($query) {
            /**
             * Quick fix magento bug in \Magento\Framework\DB\Adapter\Pdo::_splitMultiQuery(sql)
             * This algorithm does not work properly
             */
            $query = str_replace(';', '', $query);
            $words = $this->injectMatchQuery->removeStopWords(explode(' ', $query));
            $query = implode(' ', $words);
        }

        return [$text, $query];
    }
}
