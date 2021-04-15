<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Phrase;

abstract class AbstractField extends \Magento\Config\Block\System\Config\Form\Field
{
    const MODULE_NAME = 'Amasty_Xsearch';
    const CONFIG_MODULE_NAME = 'search';
    const NOTE = '';

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($this->getModuleManager() && $this->getModuleManager()->isEnabled(static::MODULE_NAME)) {
            $html = parent::render($element);
        } else {
            $html = '<tr id="row_' . static::CONFIG_MODULE_NAME . '_amasty_not_instaled"><td class="label">'
                . '<label for="brand_amasty_not_instaled">'
                . '<span>' . __('Status')
                . '</span></label></td><td class="value"><div class="control-value">' . $this->getStatus() . '</div>'
                . ($this->getNote() ? '<p class="note"><span>' . $this->getNote() . '</span></p>' : '')
                . '</td><td class="">'
                . '<input type="hidden" id="amasty_xsearch_' . static::CONFIG_MODULE_NAME . '_enabled" value="0">'
                . '</td></tr>';
        }

        return $html;
    }

    /**
     * @return string
     */
    protected function getNote()
    {
        return '';
    }

    /**
     * @return string|Phrase
     */
    protected function getStatus()
    {
        return __('Not Installed');
    }
}
