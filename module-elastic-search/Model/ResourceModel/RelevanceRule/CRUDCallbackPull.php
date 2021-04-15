<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


declare(strict_types=1);

namespace Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule;

use Amasty\ElasticSearch\Model\ResourceModel\RelevanceRule\AdditionalSaveActions\CRUDCallbackInterface;
use ArrayIterator;
use IteratorAggregate;

class CRUDCallbackPull implements IteratorAggregate
{
    const SORT_ORDER = 'sortOrder';
    const ACTION = 'action';

    /**
     * @var array[]
     *
     * @example [
     *      [
     *          'sortOrder' => 12,
     *          'actions' => $action
     *      ]
     * ]
     */
    private $crudCallbacks;

    public function __construct(
        $crudCallbacks = []
    ) {
        $this->crudCallbacks = $this->sortActions($crudCallbacks);
    }

    private function sortActions(array $actionConfig): array
    {
        usort($actionConfig, function (array $configA, array $configB) {
            $sortOrderA = $configA[self::SORT_ORDER] ?? 0;
            $sortOrderB = $configB[self::SORT_ORDER] ?? 0;

            return $sortOrderA <=> $sortOrderB;
        });

        return $actionConfig;
    }

    public function getIterator(): iterable
    {
        $actions = [];

        foreach ($this->crudCallbacks as $callback) {
            $action = $callback[self::ACTION] ?? null;

            if ($action instanceof CRUDCallbackInterface) {
                $actions[] = $action;
            }
        }

        return new ArrayIterator($actions);
    }
}
