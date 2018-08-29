<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;

/**
 * Class ResetCustomer
 */
class ResetCustomer extends Action
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $resourceCustomer;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    private $dataObjectFactory;

    /**
     * SyncCustomer constructor.
     *
     * @param Action\Context $context
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @throws \Exception
     */
    public function __construct(
        Action\Context $context,
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer,
        \Magento\Framework\DataObject\Factory $dataObjectFactory
    ) {
        parent::__construct($context);
        $this->resourceCustomer = $resourceCustomer;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TNW_QuickbooksBasic::customer');
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Exception
     */
    public function execute()
    {
        $productId = $this->getRequest()->getParam('id');

        $object = $this->dataObjectFactory->create([
            'id' => $productId,
            'quickbooks_id' => null,
            'quickbooks_sync_status' => null,
            'quickbooks_sync_token' => null,
        ]);

        $this->resourceCustomer->saveAttribute($object, 'quickbooks_id');
        $this->resourceCustomer->saveAttribute($object, 'quickbooks_sync_status');
        $this->resourceCustomer->saveAttribute($object, 'quickbooks_sync_token');

        return $this->resultRedirectFactory->create()
            ->setRefererUrl();
    }
}
