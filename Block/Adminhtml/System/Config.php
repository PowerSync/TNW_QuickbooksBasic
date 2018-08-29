<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Block\Adminhtml\System;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Config
 *
 * @package TNW\QuickbooksBasic\Block\Adminhtml\System
 */
class Config extends Field
{
    /** @var \TNW\QuickbooksBasic\Model\Quickbooks */
    protected $quickbooks;

    /**
     * @param string $buttonLabel
     *
     * @return \TNW\QuickbooksBasic\Block\Adminhtml\System\Config
     */
    public function setbuttonLabel($buttonLabel)
    {
        $this->_buttonLabel = $buttonLabel;

        return $this;
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }
}
