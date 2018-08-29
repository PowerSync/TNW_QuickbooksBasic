<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Controller\Adminhtml\Connect;

use TNW\QuickbooksBasic\Controller\Adminhtml\Quickbooks;

/**
 * Class Index
 *
 * @package TNW\QuickbooksBasic\Controller\Adminhtml\Connect
 */
class Index extends Quickbooks
{
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|
     *          \Magento\Framework\Controller\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        try {
            $response = [
                'success' => 'true',
                'message' => 'Connecting...',
                'connect_url' => $this->quickbooks->getRequestTokenUrl(),
            ];
        } catch (\Exception $e) {
            $response = ['error' => 'true', 'message' => $e->getMessage()];
        }
        $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);

        return $resultJson->setData($response);
    }
}
