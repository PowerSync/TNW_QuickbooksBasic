<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\QuickbooksBasic\Controller\Adminhtml\System\Log;

class Truncate extends \Magento\Backend\App\Action
{
    /**
     * @var \TNW\QuickbooksBasic\Model\ResourceModel\Log
     */
    protected $resourceLogger;

    /**
     * Truncate constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \TNW\QuickbooksBasic\Model\ResourceModel\Message $resourceLogger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \TNW\QuickbooksBasic\Model\ResourceModel\Message $resourceLogger
    ) {
        $this->resourceLogger = $resourceLogger;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TNW_QuickbooksBasic::system_message');
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        try {
            $this->resourceLogger->getConnection()
                ->truncateTable($this->resourceLogger->getMainTable());
        } catch (\Exception $e) {
            $this->getMessageManager()
                ->addErrorMessage($e->getMessage(), 'backend');
        }

        return $this->resultRedirectFactory->create()
            ->setRefererUrl();
    }
}
