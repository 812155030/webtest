<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


declare(strict_types=1);

namespace Amasty\ElasticSearch\Setup\Patch\Data;

use Amasty\ElasticSearch\Api\Data\StopWordInterface;
use Amasty\ElasticSearch\Api\StopWordRepositoryInterface;
use Amasty\ElasticSearch\Setup\Model\ModuleDataProvider;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\NonTransactionableInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

class DeployStopWords implements DataPatchInterface, NonTransactionableInterface
{
    const STOP_WORDS_DIR = 'stop_words';
    const FILE_EXTENSION = '.csv';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ModuleDataProvider
     */
    private $moduleDataProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LocaleResolver
     */
    private $localeResolver;

    /**
     * @var LocaleResolver
     */
    private $debug;

    /**
     * @var StopWordRepositoryInterface
     */
    private $stopWordRepository;

    public function __construct(
        StopWordRepositoryInterface $stopWordRepository,
        ModuleDataSetupInterface $moduleDataSetup,
        ModuleDataProvider $moduleDataProvider,
        StoreManagerInterface $storeManager,
        LocaleResolver $localeResolver,
        LoggerInterface $debug
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->moduleDataProvider = $moduleDataProvider;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
        $this->debug = $debug;
        $this->stopWordRepository = $stopWordRepository;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): DeployStopWords
    {
        if ($this->isCanApply()) {
            $currentStore = $this->storeManager->getStore();

            foreach ($this->storeManager->getStores(false) as $store) {
                try {
                    $this->localeResolver->emulate($store->getId());
                    $locale = $this->localeResolver->getLocale();

                    if ($locale) {
                        $this->runImportProcess($locale, $store);
                    }
                } catch (\Exception $exception) {
                    $this->debug->debug($exception->getMessage());
                }
            }

            $this->localeResolver->emulate($currentStore->getId());
        }

        return $this;
    }

    private function isCanApply(): bool
    {
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select();
        $select->from(
            $this->moduleDataSetup->getTable(StopWordInterface::TABLE_NAME),
            [new Zend_Db_Expr('COUNT(*)')]
        );

        return !$connection->fetchOne($select);
    }

    /**
     * @param string $locale
     * @param StoreInterface $store
     * @throws \Exception
     */
    private function runImportProcess(string $locale, StoreInterface $store): void
    {
        $fileName = $locale . self::FILE_EXTENSION;

        try {
            $count = $this->stopWordRepository->importStopWords(
                $this->moduleDataProvider->getModuleDataFilePath($fileName, self::STOP_WORDS_DIR),
                $store->getId()
            );
            $this->debug->debug(
                __('%1 StopWords were imported for store %2', $count, $store->getName())->render()
            );
        } catch (\InvalidArgumentException $exception) {
            $this->debug->debug(__('There are no file for locale: %1', $locale)->render());
        }
    }
}
