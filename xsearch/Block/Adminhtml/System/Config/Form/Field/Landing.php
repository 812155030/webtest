<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Block\Adminhtml\System\Config\Form\Field;

class Landing extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($this->getModuleManager() && $this->getModuleManager()->isEnabled('Amasty_Xlanding')) {
            $html = parent::render($element);
        } else {
            $html = '<tr id="row_xlanding_amasty_not_instaled"><td class="label">
                <label for="xlanding_amasty_not_instaled">
                    <span>' . __('Status') . '</span>
                </label></td><td class="value"><div class="control-value">' . __('Not Installed')
                . '</div></td><td class=""></td></tr>';
        }

        return $html;
    }
}
