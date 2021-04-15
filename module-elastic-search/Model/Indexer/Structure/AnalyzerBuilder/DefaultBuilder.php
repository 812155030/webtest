<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\Indexer\Structure\AnalyzerBuilder;

use Amasty\ElasticSearch\Api\Data\Indexer\Structure\AnalyzerBuilderInterface;
use Amasty\ElasticSearch\Model\Client\Elasticsearch as ElasticSearchClient;

class DefaultBuilder implements AnalyzerBuilderInterface
{
    const WORD_DELIMITER_VERSION_5 = 'word_delimiter';
    const WORD_DELIMITER_VERSION_6 = 'word_delimiter_graph';

    /**
     * @var EntityCollectionProvider
     */
    private $entityCollectionProvider;

    /**
     * @var \Amasty\ElasticSearch\Model\Config
     */
    private $config;

    /**
     * @var ElasticSearchClient
     */
    private $elasticClient;

    public function __construct(
        EntityCollectionProvider $entityCollectionProvider,
        \Amasty\ElasticSearch\Model\Config $config,
        ElasticSearchClient $elasticClient
    ) {
        $this->entityCollectionProvider = $entityCollectionProvider;
        $this->config = $config;
        $this->elasticClient = $elasticClient;
    }

    /**
     * @param int $storeId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build($storeId)
    {
        $analyser = [
            'analyzer' => [
                //"the A*b-1^2 O'Neil's" => ["ab12", "oneil"]
                'default' => [
                    'type'      => 'custom',
                    'tokenizer' => 'whitespace',
                    'filter'    => [
                        'stop_filter',
                        "synonym",
                        'lowercase',
                        $this->getWordDelimiterFilter()
                    ],
                ],
                //"the A*b-1^2 O'Neil's" => ["a*b-1^2", "o'neil's"]
                'allow_spec_chars' => [
                    'type'      => 'custom',
                    'tokenizer' => 'whitespace',
                    'filter'    => [
                        'stop_filter',
                        'synonym',
                        'lowercase'
                    ],
                ],
                'stem' => [
                    'type'      => 'custom',
                    'tokenizer' => 'whitespace',
                    'filter'    => [
                        'stop_filter',
                        'lowercase'
                    ]
                ]
            ],
            'filter'   => [
                $this->getWordDelimiterFilter() => [
                    // https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-word-delimiter-graph-tokenfilter.html
                    'type'                    => $this->getWordDelimiterFilter(),
                    'catenate_all'            => true,
                    'catenate_words'          => false,
                    'catenate_numbers'        => false,
                    //^ catenate all
                    'generate_word_parts'     => false,
                    'generate_number_parts'   => false,
                    'split_on_case_change'    => false,
                    'preserve_original'       => true,
                    'split_on_numerics'       => false,
                ],
                'stop_filter' => [
                    "type" => "stop",
                    "stopwords" => $this->getStopWords($storeId)
                ],
                "synonym" => [
                    "type" => "synonym",
                    "lenient" => true,
                    "synonyms" => $this->getSynonyms($storeId)
                ]
            ]
        ];

        if ($stemmingData = $this->getStemmingData($storeId)) {
            $analyser['filter']['stemming'] = [
                'type' => 'stemmer',
                'language' => $stemmingData
            ];

            $analyser['analyzer']['stem']['filter'][] = 'stemming';
        }

        $charMappings = $this->config->getCharMappings($storeId);
        if ($charMappings) {
            $analyser['char_filter']['mapped_char_filter'] = [
                'type' => 'mapping',
                'mappings' => $charMappings
            ];
            $analyser['analyzer']['default']['char_filter'] = 'mapped_char_filter';
            $analyser['analyzer']['allow_spec_chars']['char_filter'] = 'mapped_char_filter';
        }

        return $analyser;
    }

    /**
     * @param $storeId
     * @return array|string
     */
    private function getStopWords($storeId)
    {
        $usePredefined = $this->config->getUsePredefinedStopwords($storeId);
        if ($usePredefined) {
            return sprintf(
                '_%s_',
                $this->config->getStopWordsLanguage($storeId)
            );
        } else {
            $stopWords = [];
            $collection = $this->entityCollectionProvider->getStopWordCollectionFactory()->create();
            $collection->addStoreFilter($storeId);
            foreach ($collection as $stopWord) {
                $stopWords[] = preg_replace('/\s*/', '', $stopWord->getTerm());
            }
            if (!count($stopWords)) {
                $stopWords = '_none_';
            }
        }

        return $stopWords;
    }

    /**
     * @param $storeId
     * @return string
     */
    private function getStemmingData($storeId)
    {
        $usePredefined = $this->config->getUsePredefinedStemming($storeId);
        if ($usePredefined) {
            return $this->config->getStemmedLanguage($storeId);
        }

        return '';
    }

    /**
     * @param $storeId
     * @return array
     */
    private function getSynonyms($storeId)
    {
        $synonyms = [];
        $collection = $this->entityCollectionProvider->getSynonymCollectionFactory()->create();
        $collection->addStoreFilter($storeId);
        foreach ($collection as $synonym) {
            $synonyms[] = trim($synonym->getTerm());
        }

        return $synonyms ?: ['']; //can't pass empty array to elastic 5.x
    }

    /**
     * @return string
     */
    private function getWordDelimiterFilter()
    {
        $elasticVersion = $this->elasticClient->getEngineVersion();

        if (version_compare($elasticVersion, '5.0.0', '>=')
            && version_compare($elasticVersion, '6.0.0', '<=')
        ) {
            return self::WORD_DELIMITER_VERSION_5;
        }

        return self::WORD_DELIMITER_VERSION_6;
    }
}
