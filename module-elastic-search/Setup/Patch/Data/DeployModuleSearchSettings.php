<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


declare(strict_types=1);

namespace Amasty\ElasticSearch\Setup\Patch\Data;

use Amasty\ElasticSearch\Setup\Model\ModuleDataProvider;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class DeployModuleSearchSettings implements DataPatchInterface
{
    const SEARCH_CONFIG_FILE = 'search_config.json';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ModuleDataProvider
     */
    private $moduleDataProvider;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ModuleDataProvider $moduleDataProvider
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->moduleDataProvider = $moduleDataProvider;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): DeployModuleSearchSettings
    {
        $data = [
            'scope' => 'default',
            'scope_id' => 0,
            'path' => 'amasty_elastic/catalog/query_settings',
            'value' => $this->moduleDataProvider->getModuleDataFileContent(self::SEARCH_CONFIG_FILE)
        ];

        $connection = $this->moduleDataSetup->getConnection();
        $connection->insertOnDuplicate(
            $this->moduleDataSetup->getTable('core_config_data'),
            $data,
            ['value']
        );

        return $this;
    }
}
