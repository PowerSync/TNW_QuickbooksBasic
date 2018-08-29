<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Controller\Result\Redirect;
use TNW\QuickbooksBasic\Model\Quickbooks\SyncManager;

/**
 * Class SyncCustomer
 *
 * @package TNW\QuickbooksBasic\Controller\Adminhtml\Customer
 */
class SyncCustomer extends Action
{
    /**
     * @var SyncManager
     */
    private $syncManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * SyncCustomer constructor.
     *
     * @param Action\Context              $context
     * @param SyncManager                 $syncManager
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Action\Context $context,
        SyncManager $syncManager,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->syncManager = $syncManager;
        $this->customerRepository = $customerRepository;

        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var int $customerId */
        $customerId = (int) $this->getRequest()->getParam('customer_id');

        try {
            /** @var Customer $customerObject */
            $customerObject = $this->customerRepository->getById($customerId);
            $this->syncManager->syncObject($customerObject, true);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->resultRedirectFactory->create()
            ->setPath($this->_redirect->getRefererUrl());
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_QuickbooksBasic::customer'
        );
    }
}
