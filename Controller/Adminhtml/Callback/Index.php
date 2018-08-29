<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Controller\Adminhtml\Callback;

use TNW\QuickbooksBasic\Controller\Adminhtml\Quickbooks;

/**
 * Class Index
 *
 * @package TNW\QuickbooksBasic\Controller\Adminhtml\Callback
 */
class Index extends Quickbooks
{
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|
     *         \Magento\Framework\Controller\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        try {
            $this->quickbooks->grant($this->getRequest());
        } catch (\Exception $e) {
            $this->messageManager->addWarning($e->getMessage());
        }

        $this->_redirect($this->_backendUrl->getUrl(
            'adminhtml/system_config/edit/section/quickbooks'
        ));
    }
}
