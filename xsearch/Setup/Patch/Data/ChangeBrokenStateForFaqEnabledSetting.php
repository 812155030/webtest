<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Setup\Patch\Data;

use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ChangeBrokenStateForFaqEnabledSetting implements DataPatchInterface
{
    const CONFIG_PATH = 'amasty_xsearch/faq/enabled';
    const VALUE_DISABLED = 0;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection,
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        TypeListInterface $cacheTypeList
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @return void
     */
    public function apply()
    {
        try {
            $faqEnableConfig = $this->loadConfig();
        } catch (\Exception $e) {
            $faqEnableConfig = [];
        }

        if (!empty($faqEnableConfig)) {
            foreach ($faqEnableConfig as $config) {
                if ($config['value'] === '2') {
                    $this->configWriter->save(
                        self::CONFIG_PATH,
                        self::VALUE_DISABLED,
                        $config['scope'],
                        $config['scope_id']
                    );
                }
            }
        }

        $this->cacheTypeList->cleanType(ConfigCache::TYPE_IDENTIFIER);
    }

    private function loadConfig(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from($this->resourceConnection->getTableName('core_config_data'));
        $select->where('path = ?', self::CONFIG_PATH);
        $select->columns(['scope_id', 'scope', 'path', 'value']);

        return $connection->fetchAll($select);
    }

    /**
     * @return string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }
}
