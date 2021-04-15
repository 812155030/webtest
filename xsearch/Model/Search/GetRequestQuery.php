<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Search;

use Amasty\Xsearch\Model\Client\ClientWrapper;
use Amasty\Xsearch\Model\Config;

class GetRequestQuery
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ClientWrapper
     */
    private $clientWrapper;

    public function __construct(
        Config $config,
        ClientWrapper $clientWrapper
    ) {
        $this->config = $config;
        $this->clientWrapper = $clientWrapper;
    }

    public function executeExternalByFulltext(
        string $term,
        int $storeId,
        string $fulltextField,
        string $entityType
    ): array {
        $filterQuery = ['must' => [['query_string' => [
            'default_field' => $fulltextField,
            'query' => $term
        ]]]];
        $index = ClientWrapper::EXTERNAL_INDEX . '_' . $entityType;
        $query = [
            'index' => $this->clientWrapper->getIndexName($index, $storeId),
            'type'  => 'document',
            'body'  => [
                'from'      => 0,
                'size'      => 10000,
                '_source'   => [],
                'query'     => ['bool' => $filterQuery]
            ],
        ];

        return $query;
    }
}
