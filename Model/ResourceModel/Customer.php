<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Model\ResourceModel;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactoryInterface;
use TNW\QuickbooksBasic\Model\Customer\CustomAttribute;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer as QuickbooksCustomer;
use Magento\Framework\App\ResourceConnection\ConfigInterface as
    ResourceConfigInterface;

/**
 * Class Customer
 * @package TNW\QuickbooksBasic\Model\ResourceModel
 */
class Customer extends ResourceConnection
{

    /** @var  CustomAttribute */
    protected $customAttribute;

    /**
     * Customer constructor.
     * @param ResourceConfigInterface $resourceConfig
     * @param ConnectionFactoryInterface $connectionFactory
     * @param DeploymentConfig $deploymentConfig
     * @param CustomAttribute $customAttribute
     * @param string $tablePrefix
     */
    public function __construct(
        ResourceConfigInterface $resourceConfig,
        ConnectionFactoryInterface $connectionFactory,
        DeploymentConfig $deploymentConfig,
        CustomAttribute $customAttribute,
        $tablePrefix = ''
    ) {
        parent::__construct(
            $resourceConfig,
            $connectionFactory,
            $deploymentConfig,
            $tablePrefix
        );
        $this->customAttribute = $customAttribute;
    }

    /**
     * @param array             $data
     * @param CustomerInterface $customer
     */
    public function syncQuickbooksResponse(
        array $data,
        CustomerInterface $customer
    ) {
        /** @var Address $billingAddress */
        $billingAddressId = $customer->getDefaultBilling();

        /** @var Address $shippingAddress */
        $shippingAddressId = $customer->getDefaultShipping();

        /** @var string|null $customerQuickbooksId */
        $customerQuickbooksId = isset($data['Customer']['Id']) ?
            $data['Customer']['Id'] :
            null;

        /** @var string|null $customerSyncToken */
        $customerSyncToken = isset($data['Customer']['SyncToken']) ?
            $data['Customer']['SyncToken'] :
            null;

        if (!is_null($customerQuickbooksId)) {
            $customer->setCustomAttribute(
                QuickbooksCustomer::QUICKBOOKS_ID,
                $customerQuickbooksId
            );
        }
        if (!is_null($customerSyncToken)) {
            $customer->setCustomAttribute(
                QuickbooksCustomer::QUICKBOOKS_SYNC_TOKEN,
                $customerSyncToken
            );
        }
        if (!is_null($customerQuickbooksId) && !is_null($customerSyncToken)) {
            $customer->setCustomAttribute(
                QuickbooksCustomer::QUICKBOOKS_SYNC_STATUS,
                1
            );
        }

        /** @var string|null $billingAddressQuickbooksId */
        $billingAddressQuickbooksId =
            isset($data['Customer']['BillAddr']['Id']) ?
            $data['Customer']['BillAddr']['Id'] :
            null;

        /** @var string|null $shippingAddressQuickbooksId */
        $shippingAddressQuickbooksId =
            isset($data['Customer']['ShipAddr']['Id']) ?
            $data['Customer']['ShipAddr']['Id'] :
            null;

        $this->customAttribute->saveQuickbooksAttribute($customer);

        if ($billingAddressId && $billingAddressQuickbooksId) {
            $this->addressSync(
                QuickbooksCustomer::BILLING_ADDRESS_QUICKBOOKS_TABLE,
                $billingAddressId,
                $billingAddressQuickbooksId
            );
        }

        if ($shippingAddressId && $shippingAddressQuickbooksId) {
            $this->addressSync(
                QuickbooksCustomer::SHIPPINGG_ADDRESS_QUICKBOOKS_TABLE,
                $shippingAddressId,
                $shippingAddressQuickbooksId
            );
        }
    }

    /**
     * @param $table
     * @param $id
     * @param null|int $quickbooksId
     */
    protected function addressSync($table, $id, $quickbooksId = null)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->getConnection();

        $connection->insertOnDuplicate(
            $this->getTableName($table),
            [
                QuickbooksCustomer::ADDRESS_ENTITY_ID => $id,
                QuickbooksCustomer::QUICKBOOKS_ID => $quickbooksId
            ]
        );
    }
}
