<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Model\Indexer\Structure\AnalyzerBuilder;

use Amasty\ElasticSearch\Api\Data\Indexer\Structure\AnalyzerBuilderInterface;
use Amasty\ElasticSearch\Model\Indexer\Structure\AnalyzerBuilder\EntityCollectionProvider;
use Amasty\ElasticSearch\Model\Source\KuromojiReadingForm;

class Kuromoji implements AnalyzerBuilderInterface
{
    /**
     * @var EntityCollectionProvider
     */
    private $entityCollectionProvider;

    /**
     * @var \Amasty\ElasticSearch\Model\Config
     */
    private $config;

    public function __construct(
        EntityCollectionProvider $entityCollectionProvider,
        \Amasty\ElasticSearch\Model\Config $config
    ) {
        $this->entityCollectionProvider = $entityCollectionProvider;
        $this->config = $config;
    }

    /**
     * @param int $storeId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build($storeId)
    {
        $filters = $this->getFilters($storeId);
        $analyzerFilters = array_merge(array_keys($filters), ['lowercase']);
        $tokenizer = $this->getTokenizer($storeId);

        $analyser = [
            'analyzer' => [
                'default' => [
                    'type'      => 'custom',
                    'tokenizer' => key($tokenizer),
                    'filter'    => $analyzerFilters
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
            'tokenizer' => $tokenizer,
            'filter' => $this->getFilters($storeId)
        ];

        return $analyser;
    }

    private function getFilters($storeId)
    {
        $filters = [
            'stop_filter' => [
                "type" => "ja_stop",
                "stopwords" => $this->getStopWords($storeId)
            ],
            "synonym" => [
                "type" => "synonym",
                "lenient" => true,
                "synonyms" => $this->getSynonyms($storeId)
            ]
        ];
        if ($stemmingData = $this->getStemmingData($storeId)) {
            $filters['stemming'] = [
                'type' => 'kuromoji_stemmer',
                'minimum_length' => 4
            ];

        }

        if ($readingForm = $this->config->getKuromojiReadingForm($storeId)) {
            switch ($readingForm) {
                case KuromojiReadingForm::ROMAJI:
                    $filters['romaji_readingform'] = [
                        'type' => 'kuromoji_readingform',
                        'use_romaji' => true
                    ];
                    break;
                case KuromojiReadingForm::KATAKANA:
                    $filters['romaji_readingform'] = [
                        'type' => 'kuromoji_readingform',
                        'use_romaji' => false
                    ];
                    break;
            }
        }

        return $filters;
    }

    /**
     * @param int $storeId
     * @return array
     */
    private function getTokenizer($storeId)
    {
        return [
            'kuromoji_custom_tokenizer' => [
                'type' => 'kuromoji_tokenizer',
                'mode' => $this->config->getKoromojiTokenMode($storeId),
            ]
        ];
    }

    /**
     * @param $storeId
     * @return array|string
     */
    private function getStopWords($storeId)
    {
        $usePredefined = $this->config->getUsePredefinedStopwords($storeId);
        if ($usePredefined) {
            return "_japanese_";
        } else {
            $stopWords = [];
            $collection = $this->entityCollectionProvider->getStopWordCollectionFactory()->create();
            $collection->addStoreFilter($storeId);

            foreach ($collection as $stopWord) {
                $stopWords[] = preg_replace('/\s*/u', '-', $stopWord->getTerm());
            }

            if (!count($stopWords)) {
                $stopWords = '_none_';
            }
        }

        return $stopWords;
    }

    /**
     * @param $storeId
     * @return array|string
     */
    private function getStemmingData($storeId)
    {
        return $this->config->getUsePredefinedStemming($storeId);
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
}
