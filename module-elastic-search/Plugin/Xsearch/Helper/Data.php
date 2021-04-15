<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
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
            $words = $this->injectMatchQuery->removeStopWords(explode(' ', $query));
            $query = implode(' ', $words);
        }

        return [$text, $query];
    }
}
