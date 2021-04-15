<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Plugin\Search\Model\Query;

use Magento\Search\Model\Query;

class KeepQueryText
{
    public function afterLoadByQueryText(Query $subject, Query $result, string $queryText): Query
    {
        $result->setQueryText($queryText);
        return $result;
    }
}
