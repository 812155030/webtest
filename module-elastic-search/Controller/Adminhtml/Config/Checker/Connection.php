<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Controller\Adminhtml\Config\Checker;

use Amasty\ElasticSearch\Model\Client\ElasticsearchFactory;
use Amasty\ElasticSearch\Model\Source\CustomAnalyzer;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\StripTags;
use Magento\Backend\App\Action;
use Amasty\ElasticSearch\Model\Config;
use Elasticsearch\Common\Exceptions\Missing404Exception;

class Connection extends Action
{
    const ADMIN_RESOURCE = 'Amasty_ElasticSearch::config';

    const EXCEPTION_PATTERN = '/unknown analyzer/i';

    const IS_READ_ONLY_INDEX = 'amasty_is_read_only_flag';

    /**
     * @var ElasticsearchFactory
     */
    private $elasticsearchFactory;

    /**
     * @var \Amasty\ElasticSearch\Model\Config
     */
    private $config;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var StripTags
     */
    private $tagFilter;

    public function __construct(
        Context $context,
        \Amasty\ElasticSearch\Model\Config $config,
        ElasticsearchFactory $elasticsearchFactory,
        JsonFactory $resultJsonFactory,
        StripTags $tagFilter
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->tagFilter = $tagFilter;
        $this->elasticsearchFactory = $elasticsearchFactory;
        $this->config = $config;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = [
            'success' => false,
            'errorMessage' => '',
        ];
        $options = $this->getRequest()->getParams();

        try {
            if (empty($options['engine']) || $options['engine'] !== Config::ELASTIC_SEARCH_ENGINE) {
                throw new LocalizedException(
                    __('Test connection can be applied only for Amasty Elastic Search engine.')
                );
            }
            $connectionData = $this->config->prepareConnectionData($options);
            $client = $this->elasticsearchFactory->create(['options' => $connectionData]);
            $pingResult = $client->ping();

            if ($pingResult) {
                $this->checkIsReadOnly($client);
                if (isset($options['customAnalyzer'])
                    && $options['customAnalyzer'] != CustomAnalyzer::DISABLED
                ) {
                    //@codingStandardsIgnoreStart
                    try {
                        $client->deleteIndex('custom_analyzer_test_index');
                    } catch (Missing404Exception $e) {
                        // do nothing
                    }

                    $client->createIndex(
                        'custom_analyzer_test_index',
                        [
                            'settings' => [
                                'analysis' => [
                                    'analyzer' => [
                                        'default' => ['type' => $options['customAnalyzer']]
                                    ]
                                ]
                            ]
                        ]
                    );
                    //@codingStandardsIgnoreEnd
                }
                $result['success'] = $pingResult;
            } else {
                $result['errorMessage'] = $client->getDefaultErrorMessage();
            }

            // @codingStandardsIgnoreLine
        } catch (LocalizedException $e) {
            $result['errorMessage'] = $e->getMessage();
        } catch (\Exception $e) {
            if (preg_match(self::EXCEPTION_PATTERN, $e->getMessage())) {
                $result['errorMessage'] = __('To use custom analyzer you have to install matching plugin');
            } else {
                $result['errorMessage'] = $this->tagFilter->filter(__($e->getMessage()));
            }
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($result);
    }

    /**
     * @param \Amasty\ElasticSearch\Model\Client\Elasticsearch $client
     */
    private function checkIsReadOnly(\Amasty\ElasticSearch\Model\Client\Elasticsearch $client)
    {
        try {
            $client->createIndex(self::IS_READ_ONLY_INDEX, []);
            $client->deleteIndex(self::IS_READ_ONLY_INDEX);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage(
                __('Please check the state of read_only_allow_delete setting in Elasticsearch server configuration.')
            );
        }
    }
}
