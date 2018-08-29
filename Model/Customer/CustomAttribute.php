<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Backend\Customer as BackendCustomer;
use Magento\Customer\Model\ResourceModel\Customer as ResourceCustomer;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer;

/**
 * Class CustomAttribute
 *
 * @package TNW\QuickbooksBasic\Model\Customer
 */
class CustomAttribute
{

    /** @var \Magento\Customer\Model\ResourceModel\Customer */
    protected $resourceCustomer;

    /** @var \Magento\Customer\Model\Backend\Customer */
    protected $backendCustomer;

    /**
     * CustomAttribute constructor.
     *
     * @param ResourceCustomer $resourceCustomer
     * @param BackendCustomer  $backendCustomer
     */
    public function __construct(
        ResourceCustomer $resourceCustomer,
        BackendCustomer $backendCustomer
    ) {
        $this->resourceCustomer = $resourceCustomer;
        $this->backendCustomer = $backendCustomer;
    }

    /**
     * Update custom Quickbooks attributes for customer
     *
     * @param CustomerInterface $customer
     */
    public function saveQuickbooksAttribute(
        CustomerInterface $customer
    ) {
        /** @var BackendCustomer $backendCustomerObject */
        $backendCustomerObject = $this->backendCustomer->load(
            $customer->getId()
        );

        foreach ($this->getProcessedAttributeCodes() as $attributeCode) {
            $this->processAttributeCode(
                $customer,
                $backendCustomerObject,
                $attributeCode
            );
        }

        $backendCustomerObject->reindex();
    }

    /**
     * @return array
     */
    protected function getProcessedAttributeCodes()
    {
        return [
            Customer::QUICKBOOKS_ID,
            Customer::QUICKBOOKS_SYNC_STATUS,
            Customer::QUICKBOOKS_SYNC_TOKEN,
        ];
    }

    /**
     * @param CustomerInterface $customer
     * @param BackendCustomer   $backendCustomerObject
     * @param string            $attributeCode
     *
     * @return mixed|null
     */
    protected function processAttributeCode(
        CustomerInterface $customer,
        BackendCustomer $backendCustomerObject,
        $attributeCode
    ) {
        $returnValue =
            $customer->getCustomAttribute($attributeCode) ?
                $customer->getCustomAttribute($attributeCode)->getValue() :
                null;

        $backendCustomerObject->setData($attributeCode, $returnValue);

        if ($backendCustomerObject->getOrigData($attributeCode) !=
            $backendCustomerObject->getData($attributeCode)
        ) {
            $this->resourceCustomer->saveAttribute(
                $backendCustomerObject,
                $attributeCode
            );
        }

        return $returnValue;
    }
}
