<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\Indexer\Structure\AnalyzerBuilder;

use Amasty\ElasticSearch\Model\ResourceModel\StopWord\CollectionFactory as StopWordCollectionFactory;
use Amasty\ElasticSearch\Model\ResourceModel\Synonym\CollectionFactory as SynoymCollectionFactory;

class EntityCollectionProvider
{
    /**
     * @var StopWordCollectionFactory
     */
    private $stopWordCollectionFactory;

    /**
     * @var SynoymCollectionFactory
     */
    private $synoymCollectionFactory;

    public function __construct(
        StopWordCollectionFactory $stopWordCollectionFactory,
        SynoymCollectionFactory $synoymCollectionFactory
    ) {
        $this->stopWordCollectionFactory = $stopWordCollectionFactory;
        $this->synoymCollectionFactory = $synoymCollectionFactory;
    }

    /**
     * @return StopWordCollectionFactory
     */
    public function getStopWordCollectionFactory()
    {
        return $this->stopWordCollectionFactory;
    }

    /**
     * @return SynoymCollectionFactory
     */
    public function getSynonymCollectionFactory()
    {
        return $this->synoymCollectionFactory;
    }
}
