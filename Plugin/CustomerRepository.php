<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use TNW\QuickbooksBasic\Model\Customer\CustomAttribute;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer as QuickbooksCustomerModel;
use TNW\QuickbooksBasic\Model\Quickbooks\SyncManager;

/**
 * Class CustomerRepository
 */
class CustomerRepository
{
    const SUCCESS_MESSAGE_REALTIME = "Quickbooks: Magento customer '%1' was successfully synchronized";
    const SUCCESS_MESSAGE_QUEUE = "Quickbooks: Magento customer '%1' was added to queue";

    /** @var LoggerInterface */
    protected $logger;

    /** @var string */
    protected $isCustomerExist;

    /**  @var ScopeConfigInterface */
    protected $coreConfig;

    /** @var  CustomAttribute */
    protected $customAttribute;

    /** @var  ManagerInterface */
    protected $messageManager;

    /** @var State */
    protected $state;

    /** @var RequestInterface|null */
    protected $request;

    /** @var SyncManager */
    private $syncManager;

    /**
     * CustomerRepository constructor.
     *
     * @param SyncManager          $syncManager
     * @param LoggerInterface      $logger
     * @param ScopeConfigInterface $coreConfig
     * @param CustomAttribute      $customAttribute
     * @param ManagerInterface     $messageManager
     * @param State                $state
     * @param RequestInterface     $request
     */
    public function __construct(
        SyncManager $syncManager,
        LoggerInterface $logger,
        ScopeConfigInterface $coreConfig,
        CustomAttribute $customAttribute,
        ManagerInterface $messageManager,
        State $state,
        RequestInterface $request
    ) {
        $this->logger = $logger;
        $this->coreConfig = $coreConfig;
        $this->customAttribute = $customAttribute;
        $this->messageManager = $messageManager;
        $this->state = $state;
        $this->request = $request;
        $this->syncManager = $syncManager;
    }

    /**
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface           $customer
     */
    public function beforeSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $customer
    ) {
        $this->isCustomerExist = $customer->getId();
    }

    /**
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface           $customer
     *
     * @return CustomerInterface
     */
    public function afterSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $customer
    ) {

        /** @var string $quickbooksGeneralActive */
        $quickbooksGeneralActive =
            (int) $this->coreConfig->getValue('quickbooks/general/active');
        /** @var string $quickbooksCustomerActive */
        $quickbooksCustomerActive =
            (int) $this->coreConfig->getValue(
                'quickbooks_customer/customer/active'
            );

        /** @var bool $isAdminArea */
        $isAdminArea = $this->state->getAreaCode() == Area::AREA_ADMINHTML;

        if ($quickbooksGeneralActive &&
            $customer->getId() === $this->isCustomerExist
        ) {
            try {
                if ($quickbooksCustomerActive) {
                    $showResultMessage = false;
                    $responseBody = $this->syncManager->syncObject($customer, $showResultMessage);

                    if (!empty($responseBody)
                    ) {
                        $successMessage = ($this->syncManager->syncTypeRealTime()) ?
                            self::SUCCESS_MESSAGE_REALTIME : self::SUCCESS_MESSAGE_QUEUE;
                        /** @var string $fullName */
                        $fullName = $customer->getFirstname() . ' ' .
                            $customer->getLastname();

                        if ($isAdminArea) {
                            $this->messageManager->addSuccessMessage(
                                __($successMessage, $fullName)
                            );
                        }
                    }
                } else {
                    $this->resetSyncStatus($customer);
                }
            } catch (\Exception $e) {
                if ($isAdminArea) {
                    $this->messageManager->addError($e->getMessage());
                }

                $this->logger->error($e->getMessage());
            }
        }

        return $customer;
    }

    /**
     * Is this inline customer editor
     *
     * @return bool
     */
    protected function isInlineEditor()
    {
        return $this->request->getActionName() == 'inlineEdit';
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
