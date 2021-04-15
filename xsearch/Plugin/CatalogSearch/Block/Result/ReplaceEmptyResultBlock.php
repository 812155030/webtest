<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Plugin\CatalogSearch\Block\Result;

use Amasty\Xsearch\Model\Config;
use Magento\CatalogSearch\Block\Result;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Widget\Model\Template\Filter;

class ReplaceEmptyResultBlock
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var BlockRepositoryInterface
     */
    private $blockRepository;

    /**
     * @var Filter
     */
    private $filter;

    public function __construct(
        Config $config,
        BlockRepositoryInterface $blockRepository,
        Filter $filter
    ) {
        $this->config = $config;
        $this->blockRepository = $blockRepository;
        $this->filter = $filter;
    }

    /**
     * @param Result $subject
     * @param callable $proceed
     * @return string
     * @see Result::toHtml()
     */
    public function aroundToHtml(Result $subject, callable $proceed): string
    {
        $blockId = $this->config->getResultBlockId();

        if ($blockId && !$subject->getResultCount()) {
            $result = $this->getBlockContent($blockId);
        }

        return $result ?? $proceed();
    }

    private function getBlockContent(int $blockId): string
    {
        $content = $this->blockRepository->getById($blockId)->getContent();

        return $this->filter->filter((string)$content);
    }
}
