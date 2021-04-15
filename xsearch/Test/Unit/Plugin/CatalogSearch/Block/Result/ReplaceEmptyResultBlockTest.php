<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Test\Unit\Plugin\CatalogSearch\Block\Result;

use Amasty\Xsearch\Model\Config;
use Amasty\Xsearch\Plugin\CatalogSearch\Block\Result\ReplaceEmptyResultBlock;
use Amasty\Xsearch\Test\Unit\Traits;
use Magento\CatalogSearch\Block\Result;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Model\BlockRepository;
use Magento\Framework\DataObject;
use Magento\Widget\Model\Template\Filter;

/**
 * Class ReplaceEmptyResultBlockTest
 * test ReplaceEmptyResultBlock Plugin
 *
 * @see ReplaceEmptyResultBlock
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReplaceEmptyResultBlockTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @covers ReplaceEmptyResultBlock::aroundToHtml
     *
     * @dataProvider aroundToHtmlDataProvider
     *
     * @throws \ReflectionException
     */
    public function testAroundToHtml(
        int $blockId,
        int $resultCount,
        DataObject $block,
        callable $proceed,
        string $expectedResult
    ): void {
        $config = $this->createPartialMock(Config::class, ['getResultBlockId']);
        $config->expects($this->any())->method('getResultBlockId')->willReturn($blockId);

        $subject = $this->createPartialMock(Result::class, ['getResultCount']);
        $subject->expects($this->any())->method('getResultCount')->willReturn($resultCount);

        $blockRepository = $this->createPartialMock(BlockRepository::class, ['getById']);
        $blockRepository->expects($this->any())->method('getById')->willReturn($block);

        $filter = $this->createPartialMock(Filter::class, ['filter']);
        $filter->expects($this->any())->method('filter')->willReturn($block->getContent());

        $entity = $this->createPartialMock(ReplaceEmptyResultBlock::class, []);
        $this->setProperty($entity, 'config', $config, ReplaceEmptyResultBlock::class);
        $this->setProperty($entity, 'filter', $filter, ReplaceEmptyResultBlock::class);
        $this->setProperty(
            $entity,
            'blockRepository',
            $blockRepository,
            ReplaceEmptyResultBlock::class
        );

        $actualResult = $entity->aroundToHtml($subject, $proceed);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * Data provider for aroundToHtml test
     * @return array
     */
    public function aroundToHtmlDataProvider(): array
    {
        return [
            [
                1,
                0,
                new DataObject([BlockInterface::CONTENT => 'qqq']),
                function () {
                    return 'aaa';
                },
                'qqq'
            ],
            [
                1,
                5,
                new DataObject([BlockInterface::CONTENT => 'qqq']),
                function () {
                    return 'aaa';
                },
                'aaa'
            ],
            [
                0,
                5,
                new DataObject([BlockInterface::CONTENT => 'qqq']),
                function () {
                    return 'aaa';
                },
                'aaa'
            ],
            [
                0,
                0,
                new DataObject([BlockInterface::CONTENT => 'qqq']),
                function () {
                    return 'aaa';
                },
                'aaa'
            ]
        ];
    }
}
