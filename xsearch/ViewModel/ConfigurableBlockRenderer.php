<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\ViewModel;

use Amasty\Xsearch\Model\Config;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class ConfigurableBlockRenderer implements ArgumentInterface
{
    const BLOCK_NAME = 'block_name';
    const CHECK_CONFIGS = 'can_render_check_configs';
    const ORDER_CONFIG = 'order_config';
    const BLOCK_CLASS = 'class';

    /**
     * @var array
     */
    private $renderingConfig;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        LayoutInterface $layout,
        Config $config,
        array $renderingConfig = []
    ) {
        $this->renderingConfig = $this->parseConfig($renderingConfig);
        $this->layout = $layout;
        $this->config = $config;
    }

    /**
     * @param Template[] $blocks
     * @return string[]
     */
    public function getBlocks(): array
    {
        $blockNamesForRender = $this->getBlocksForRender();
        $result = [];
        usort($blockNamesForRender, function (string $blockNameA, string $blockNameB): int {
            $positionA = $this->getBlockPosition($blockNameA);
            $positionB = $this->getBlockPosition($blockNameB);

            return $positionA <=> $positionB;
        });

        foreach ($blockNamesForRender as $blockName) {
            $result[$blockName] = $this->layout->createBlock($this->renderingConfig[$blockName][self::BLOCK_CLASS]);
        }

        return $result;
    }

    private function getBlocksForRender(): array
    {
        return array_filter(array_keys($this->renderingConfig), function (string $blockName): bool {
            return $this->isCanRender($blockName);
        });
    }

    private function parseConfig(array $config): array
    {
        $parsedConfig = [];

        foreach ($config as $itemConfig) {
            $parsedConfig[$itemConfig[self::BLOCK_NAME]] = $itemConfig;
        }

        return $parsedConfig;
    }

    private function getBlockPosition(string $blockName): int
    {
        $positionConfigPath = $this->renderingConfig[$blockName][self::ORDER_CONFIG] ?? "$blockName/position";

        return (int)$this->config->getModuleConfig($positionConfigPath);
    }

    private function isCanRender(string $blockName): bool
    {
        return array_reduce(
            $this->renderingConfig[$blockName][self::CHECK_CONFIGS],
            function (bool $carry, string $configPath): bool {
                return $carry && $this->config->getModuleConfig($configPath);
            },
            true
        );
    }
}
