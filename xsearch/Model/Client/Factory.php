<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Client;

use Amasty\ElasticSearch\Model\Client\Elasticsearch as AmastyElasticsearch;
use Amasty\Xsearch\Model\Config;
use Magento\Elasticsearch\Elasticsearch5\Model\Client\Elasticsearch as Elasticsearch5;
use Magento\Elasticsearch6\Model\Client\Elasticsearch as Elasticsearch6;
use Magento\Elasticsearch7\Model\Client\Elasticsearch as Elasticsearch7;
use Magento\Framework\ObjectManagerInterface;

class Factory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $clientPool;

    /**
     * @var AmastyElasticsearch|Elasticsearch5|Elasticsearch6|Elasticsearch7|null
     */
    private $client = null;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        ObjectManagerInterface $objectManager,
        Config $config,
        array $clientPool = []
    ) {
        $this->objectManager = $objectManager;
        $this->clientPool = $clientPool;
        $this->config = $config;
    }

    /**
     * @return AmastyElasticsearch|Elasticsearch6|Elasticsearch7|Elasticsearch5|mixed|null
     */
    public function getClient()
    {
        $engine = $this->config->getEngine();
        $options = $this->config->getConnectionData();
        if (!$this->client && isset($this->clientPool[$engine])) {
            $this->client = $this->objectManager->create($this->clientPool[$engine], ['options' => $options]);
        }

        return $this->client;
    }
}
