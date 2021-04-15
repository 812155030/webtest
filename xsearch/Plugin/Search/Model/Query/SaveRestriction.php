<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Plugin\Search\Model\Query;

use Amasty\Xsearch\Controller\Redirect\Index;
use Magento\Search\Model\Query;

class SaveRestriction
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->request = $request;
    }

    public function aroundSaveNumResults(Query $subject, callable $proceed, int $numResults): Query
    {
        if ($this->request->getParam(Index::AMSEARCH_404_REDIRECT) !== null) {
            return $subject;
        }

        return $proceed($numResults);
    }

    public function aroundSaveIncrementalPopularity(Query $subject, callable $proceed): Query
    {
        if ($this->request->getParam(Index::AMSEARCH_404_REDIRECT) !== null) {
            return $subject;
        }

        return $proceed();
    }
}
