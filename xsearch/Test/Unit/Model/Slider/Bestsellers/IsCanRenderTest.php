<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Test\Unit\Model\Slider\Bestsellers;

use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\Slider\Bestsellers\IsCanRender;
use Amasty\Xsearch\Model\Slider\Bestsellers\ProductsProvider;
use Amasty\Xsearch\Test\Unit\Traits\ObjectManagerTrait;
use Amasty\Xsearch\Test\Unit\Traits\ReflectionTrait;
use Magento\Catalog\Model\Product;
use Magento\Framework\Module\Manager;
use PHPUnit\Framework\TestCase;

/**
 * Class IsCanRenderTest
 * test \Amasty\Xsearch\Model\Slider\Bestsellers\IsCanRender
 *
 * @see \Amasty\Xsearch\Model\Slider\Bestsellers\IsCanRender
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IsCanRenderTest extends TestCase
{
    use ObjectManagerTrait;
    use ReflectionTrait;

    /**
     * @covers \Amasty\Xsearch\Model\Slider\Bestsellers\IsCanRender::execute
     *
     * @dataProvider isCanRenderDataProvider
     *
     * @param bool $isBestsellersBlockEnabled
     * @param bool $isSortingEnabled
     * @param Product[] $products
     * @param bool $expectedResult
     * @throws \ReflectionException
     */
    public function testExecute(
        bool $isBestsellersBlockEnabled,
        bool $isSortingEnabled,
        array $products,
        bool $expectedResult
    ): void {
        $config = $this->createPartialMock(Config::class, ['isBestsellersBlockEnabled']);
        $config
            ->expects($this->any())
            ->method('isBestsellersBlockEnabled')
            ->willReturn($isBestsellersBlockEnabled);

        $productsProvider = $this->createPartialMock(ProductsProvider::class, ['getProducts']);
        $productsProvider->expects($this->any())->method('getProducts')->willReturn($products);

        $moduleManager = $this->createPartialMock(Manager::class, ['isEnabled']);
        $moduleManager
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn($isSortingEnabled);

        $entity = $this->createPartialMock(IsCanRender::class, []);
        $this->setProperty($entity, 'config', $config, IsCanRender::class);
        $this->setProperty(
            $entity,
            'productsProvider',
            $productsProvider,
            IsCanRender::class
        );
        $this->setProperty(
            $entity,
            'moduleManager',
            $moduleManager,
            IsCanRender::class
        );

        $this->assertEquals($expectedResult, $entity->execute());
    }

    /**
     * Data provider for isCanRender test
     *
     * @return array[]
     */
    public function isCanRenderDataProvider(): array
    {
        $product = $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();

        return [
            [
                true,
                true,
                [$product],
                true
            ],
            [
                true,
                false,
                [$product],
                false
            ],
            [
                true,
                true,
                [],
                false
            ],
            [
                false,
                true,
                [$product],
                false
            ],
            [
                false,
                false,
                [],
                false
            ],
            [
                false,
                true,
                [],
                false
            ],
            [
                true,
                false,
                [],
                false
            ],
            [
                false,
                false,
                [$product],
                false
            ],
            [
                false,
                false,
                [],
                false
            ],
        ];
    }
}
