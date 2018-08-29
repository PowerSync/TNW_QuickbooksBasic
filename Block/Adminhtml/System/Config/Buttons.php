<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use TNW\QuickbooksBasic\Block\Adminhtml\System\Config;
use TNW\QuickbooksBasic\Model\Quickbooks;

/**
 * Class Buttons
 *
 * @package TNW\QuickbooksBasic\Block\Adminhtml\System\Config
 */
class Buttons extends Config
{
    /** @var string */
    protected $disconnectButtonLabel = 'Disconnect';
    protected $testButtonLabel = 'Test';
    protected $quickbooks;

    /**
     * Buttons constructor.
     *
     * @param Context    $context
     * @param Quickbooks $quickbooks
     * @param array      $data
     */
    public function __construct(
        Context $context,
        Quickbooks $quickbooks,
        array $data = []
    ) {
        $this->quickbooks = $quickbooks;
        $this->disconnectButtonLabel = __('Disconnect');
        $this->testButtonLabel = __('Test Connection');
        parent::__construct($context, $data);
    }

    /* (non-PHPdoc)
     * @see \Magento\Framework\View\Element\AbstractBlock::_prepareLayout()
     */
    /**
     * Preparing global layout
     *
     * You can redefine this method in child classes for changing layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/buttons.phtml');
        }

        return $this;
    }

    /* (non-PHPdoc)
     * @see \Magento\Config\Block\System\Config\Form\Field::_getElementHtml()
     */
    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $isConnected = (bool)$this->quickbooks->getAccessToken();
        $this->addData(
            [
                'disconnect_button_label' => __($this->disconnectButtonLabel),
                'test_button_label' => __($this->testButtonLabel),
                'html_id' => $element->getHtmlId(),
                'connect_ajax_url' =>
                    $this->_urlBuilder->getUrl('quickbooks/connect'),
                'disconnect_ajax_url' =>
                    $this->_urlBuilder->getUrl('quickbooks/disconnect'),
                'test_ajax_url' =>
                    $this->_urlBuilder->getUrl('quickbooks/test'),
                'is_connected' => $isConnected,
                'test_orange' => $isConnected,
            ]
        );

        return $this->_toHtml();
    }
}
