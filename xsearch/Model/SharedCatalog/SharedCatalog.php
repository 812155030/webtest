<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


declare(strict_types=1);

namespace Amasty\Xsearch\Model\SharedCatalog;

use Magento\Framework\Module\Manager;

class SharedCatalog
{
    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var Resolver|null
     */
    private $sharedCatalog = null;

    public function __construct(
        Manager $moduleManager,
        Resolver $sharedCatalog
    ) {
        $this->moduleManager = $moduleManager;
        $this->init($sharedCatalog);
    }

    /**
     * @param Resolver
     */
    private function init($sharedCatalog)
    {
        if ($this->moduleManager->isEnabled('Magento_SharedCatalog')
            && $sharedCatalog->isEnabled()
        ) {
            $this->sharedCatalog = $sharedCatalog;
        }
    }

    public function get(): ?Resolver
    {
        return $this->sharedCatalog;
    }
}
