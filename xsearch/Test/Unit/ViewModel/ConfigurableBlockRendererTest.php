<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Test\Unit\ViewModel;

use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Test\Unit\Traits\ObjectManagerTrait;
use Amasty\Xsearch\Test\Unit\Traits\ReflectionTrait;
use Amasty\Xsearch\ViewModel\ConfigurableBlockRenderer;
use Magento\Framework\View\Element\Template;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigurableBlockRendererTest
 * test \Amasty\Xsearch\ViewModel\ConfigurableBlockRenderer
 *
 * @see \Amasty\Xsearch\ViewModel\ConfigurableBlockRenderer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurableBlockRendererTest extends TestCase
{
    use ObjectManagerTrait;
    use ReflectionTrait;

    const FIRST_CONFIG = 'test/first';
    const SECOND_CONFIG = 'test/second';
    const POSITION_CONFIG_FIRST = 'test/position';
    const POSITION_CONFIG_SECOND = 'test_second/position';
    const POSITION_CONFIG_THIRD = 'test_third/position';

    public function getRenderingConfig(): array
    {
        return [
            'test' => [
                ConfigurableBlockRenderer::BLOCK_NAME => 'test',
                ConfigurableBlockRenderer::CHECK_CONFIGS => [
                    'test/first',
                    'test/second'
                ],
                ConfigurableBlockRenderer::BLOCK_CLASS => Template::class
            ],
            'test_second' => [
                ConfigurableBlockRenderer::BLOCK_NAME => 'test_second',
                ConfigurableBlockRenderer::CHECK_CONFIGS => [
                    'test/first'
                ],
                ConfigurableBlockRenderer::ORDER_CONFIG => 'test_second/position',
                ConfigurableBlockRenderer::BLOCK_CLASS => Template::class
            ],
            'test_third' => [
                ConfigurableBlockRenderer::BLOCK_NAME => 'test_third',
                ConfigurableBlockRenderer::CHECK_CONFIGS => [],
                ConfigurableBlockRenderer::ORDER_CONFIG => 'test_third/position',
                ConfigurableBlockRenderer::BLOCK_CLASS => Template::class
            ]
        ];
    }

    private function getConfigMockValues(): array
    {
        return [
          self::FIRST_CONFIG => true,
          self::SECOND_CONFIG => false,
          self::POSITION_CONFIG_FIRST => 1,
          self::POSITION_CONFIG_SECOND => 11,
          self::POSITION_CONFIG_THIRD => 8
        ];
    }

    public function testGetBlocks(): void
    {
        $config = $this->createPartialMock(Config::class, ['getModuleConfig']);
        $config->expects($this->any())
            ->method('getModuleConfig')
            ->willReturnCallback([$this, 'getMockedConfig']);

        $block = $this->getMockBuilder(Template::class)->disableOriginalConstructor()->getMock();
        $layout = $this->createPartialMock(\Magento\Framework\View\Layout::class, ['createBlock']);
        $layout->expects($this->any())->method('createBlock')->willReturn($block);

        $entity = $this->createPartialMock(ConfigurableBlockRenderer::class, []);

        $this->setProperty($entity, 'config', $config, ConfigurableBlockRenderer::class);
        $this->setProperty($entity, 'layout', $layout, ConfigurableBlockRenderer::class);
        $this->setProperty(
            $entity,
            'renderingConfig',
            $this->getRenderingConfig(),
            ConfigurableBlockRenderer::class
        );

        $expectedResult = ['test_third' => $block, 'test_second' => $block];
        $actualResult = $entity->getBlocks();

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function getMockedConfig(string $path, ?int $storeId)
    {
        $mockedValues = $this->getConfigMockValues();

        return $mockedValues[$path] ?? null;
    }
}
