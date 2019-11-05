<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\QuickbooksBasic\Controller\Adminhtml\System;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultFactory;

class Notification extends Action
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var \Magento\AdminNotification\Model\ResourceModel\System\Message\Collection
     */
    private $messageCollection;
    /**
     * @var \TNW\QuickbooksBasic\Model\SystemMessages
     */
    private $systemMessages;

    public function __construct(
        Context $context,
        \Magento\AdminNotification\Model\ResourceModel\System\Message\Collection $collection,
        \TNW\QuickbooksBasic\Model\SystemMessages $systemMessages,
        FileFactory $fileFactory
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
        $this->context = $context;
        $this->messageCollection = $collection;
        $this->systemMessages = $systemMessages;
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
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute()
    {
        $this->systemMessages->setDisabledMessageHash();
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        return  $result;
    }
}
