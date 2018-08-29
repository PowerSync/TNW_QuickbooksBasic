<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\QuickbooksBasic\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use TNW\QuickbooksBasic\Model\Quickbooks;

/**
 * Class Hint
 * @package TNW\QuickbooksBasic\Block\Adminhtml\System\Config\Fieldset
 */
class Hint extends Template implements RendererInterface
{
    /**
     * @var \TNW\QuickbooksBasic\Model\Quickbooks\Company
     */
    private $quickbooksCompany;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * Hint constructor.
     * @param Template\Context $context
     * @param \TNW\QuickbooksBasic\Model\Quickbooks\Company $quickbooksCompany
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Quickbooks\Company $quickbooksCompany,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quickbooksCompany = $quickbooksCompany;
        $this->messageManager = $messageManager;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if (!$this->quickbooksCompany->getIsActive() || !(bool)$this->quickbooksCompany->getAccessToken()) {
            return '';
        }

        try {
            $data = $this->quickbooksCompany->getQuickbooksService()
                ->checkResponse($this->quickbooksCompany->read());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return '';
        }

        $domain = $this->_scopeConfig->getValue(Quickbooks\Company::QUICKBOOKS_URL);
        $type = $this->_scopeConfig->getValue(Quickbooks\Company::XML_PATH_QUICKBOOKS_ENVIRONMENT);

        return sprintf(
            '<tr id="row_%s"><td class="label"></td><td class="value">%s</td></tr>',
            $element->getHtmlId(),
            __(
                'Connected to <a href="https://%1/app/homepage" target="_blank">%2</a> company in the %3 environment.',
                $domain,
                !empty($data['CompanyInfo']['CompanyName'])?$data['CompanyInfo']['CompanyName']:'',
                $type ? 'Sandbox' : 'Production'
            )
        );
    }
}
