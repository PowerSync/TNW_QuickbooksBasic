<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Plugin;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use TNW\QuickbooksBasic\Model\Customer\CustomAttribute;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer as QuickbooksCustomerModel;
use TNW\QuickbooksBasic\Model\Quickbooks\SyncManager;

/**
 * Class AddressRepository
 *
 * @package TNW\QuickbooksBasic\Plugin
 */
class AddressRepository
{
    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ScopeConfigInterface */
    protected $coreConfig;

    /** @var  CustomAttribute */
    protected $customAttribute;

    /** @var  ManagerInterface */
    protected $messageManager;
    /**
     * @var SyncManager
     */
    private $syncManager;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @param SyncManager $syncManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $coreConfig
     * @param CustomAttribute $customAttribute
     * @param ManagerInterface $messageManager
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     */
    public function __construct(
        SyncManager $syncManager,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        ScopeConfigInterface $coreConfig,
        CustomAttribute $customAttribute,
        ManagerInterface $messageManager,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry
    ) {
        $this->syncManager = $syncManager;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->coreConfig = $coreConfig;
        $this->customAttribute = $customAttribute;
        $this->messageManager = $messageManager;
        $this->customerRegistry = $customerRegistry;
    }

    /**
     * @param AddressRepositoryInterface $subject
     * @param AddressInterface           $result
     *
     * @return AddressInterface
     */
    public function afterSave(
        AddressRepositoryInterface $subject,
        AddressInterface $result
    ) {
        /** @var int|null $customerId */
        $customerId = $result->getCustomerId();

        $this->customerRegistry->remove($customerId);

        /** @var CustomerInterface $customer */
        $customer = $this->customerRepository->getById($customerId);

        /** @var string $quickbooksGeneralActive */
        $quickbooksGeneralActive =
            (int) $this->coreConfig->getValue('quickbooks/general/active');

        /** @var string $quickbooksCustomerActive */
        $quickbooksCustomerActive =
            (int) $this->coreConfig->getValue(
                'quickbooks_customer/customer/active'
            );

        /** @var bool $isAdminArea */
        if ($quickbooksGeneralActive) {
            try {
                if ($quickbooksCustomerActive) {
                    $this->syncManager->syncObject($customer);
                } else {
                    $this->resetSyncStatus($customer);
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->logger->error($e->getMessage());
            }
        }

        return $result;
    }

    /**
     * @param CustomerInterface $customer
     */
    protected function resetSyncStatus($customer)
    {
        $customer->setCustomAttribute(
            QuickbooksCustomerModel::QUICKBOOKS_SYNC_STATUS,
            0
        );

        $this->customAttribute->saveQuickbooksAttribute($customer);
    }
}
