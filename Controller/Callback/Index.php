<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Controller\Callback;

use TNW\QuickbooksBasic\Model\Quickbooks as ModelQuickbooks;
use TNW\QuickbooksBasic\Model\Exception\LoggingException;

/**
 * Class Index
 *
 * @package TNW\QuickbooksBasic\Controller\Adminhtml\Callback
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $backendUrl;

    /**
     * @var ModelQuickbooks
     */
    private $quickBooks;

    /**
     * Index constructor.
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Magento\Framework\App\Action\Context $context
     * @param ModelQuickbooks $quickBooks
     */
    public function __construct(
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\App\Action\Context $context,
        ModelQuickbooks $quickBooks
    ) {
        parent::__construct($context);
        $this->backendUrl = $backendUrl;
        $this->quickBooks = $quickBooks;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $this->quickBooks->grant($this->getRequest());
        } catch (\TNW\QuickbooksBasic\Service\Exception\InvalidStateException $e) {
            $this->_redirect($this->backendUrl->getBaseUrl());
            return;
        } catch (LoggingException $e) {
            $this->messageManager->addWarning($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addWarning($e->getMessage());
        }
        $this->_redirect($this->backendUrl->getUrl(
            'adminhtml/system_config/edit/section/quickbooks'
        ));
    }
}
