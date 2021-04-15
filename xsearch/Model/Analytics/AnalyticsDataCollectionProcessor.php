<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\Analytics;

use Amasty\Xsearch\Model\Analytics\DataCollectors\AnalyticsDataCollectorInterface;

class AnalyticsDataCollectionProcessor
{
    const TELEMETRY_OBJECT_TYPE = 'type';

    /**
     * @var AnalyticsDataCollectorInterface[]
     */
    private $dataCollectors;

    public function __construct(
        array $dataCollectors = []
    ) {
        $this->dataCollectors = $dataCollectors;
    }

    public function process(array $telemetryData): void
    {
        foreach ($telemetryData as $telemetryPart) {
            foreach ($this->dataCollectors as $dataCollector) {
                if ($dataCollector instanceof AnalyticsDataCollectorInterface
                && in_array($telemetryPart[self::TELEMETRY_OBJECT_TYPE], $dataCollector->getIdentifiers())
                ) {
                    $dataCollector->collect($telemetryPart);
                }
            }
        }
    }
}
