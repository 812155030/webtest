<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Test\Unit\Model\Slider\RecentlyViewed;

use Amasty\Xsearch\Model\Authentication;
use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Model\Slider\RecentlyViewed\IsCanRender;
use Amasty\Xsearch\Model\Slider\RecentlyViewed\ProductsProvider;
use Amasty\Xsearch\Test\Unit\Traits\ObjectManagerTrait;
use Amasty\Xsearch\Test\Unit\Traits\ReflectionTrait;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

/**
 * Class IsCanRenderTest
 * test \Amasty\Xsearch\Model\Slider\RecentlyViewed\IsCanRender
 *
 * @see \Amasty\Xsearch\Model\Slider\RecentlyViewed\IsCanRender
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IsCanRenderTest extends TestCase
{
    use ObjectManagerTrait;
    use ReflectionTrait;

    /**
     * @covers \Amasty\Xsearch\Model\Slider\RecentlyViewed\IsCanRender::execute
     *
     * @dataProvider isCanRenderDataProvider
     *
     * @param bool $isEnabled
     * @param bool $isAuthenticated
     * @param Product[] $products
     * @param bool $expectedResult
     * @throws \ReflectionException
     */
    public function testExecute(
        bool $isEnabled,
        array $products,
        bool $expectedResult
    ): void {
        $config = $this->createPartialMock(Config::class, ['isRecentlyViewedEnabled']);
        $config->expects($this->any())->method('isRecentlyViewedEnabled')->willReturn($isEnabled);

        $productsProvider = $this->createPartialMock(ProductsProvider::class, ['getProducts']);
        $productsProvider->expects($this->any())->method('getProducts')->willReturn($products);

        $entity = $this->createPartialMock(IsCanRender::class, []);
        $this->setProperty($entity, 'config', $config, IsCanRender::class);
        $this->setProperty(
            $entity,
            'productsProvider',
            $productsProvider,
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
                [$product],
                true
            ],
            [
                false,
                [$product],
                false
            ],
            [
                false,
                [],
                false
            ],
            [
                true,
                [],
                false
            ],
        ];
    }
}
