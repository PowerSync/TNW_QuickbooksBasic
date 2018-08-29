<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Address;

/** @var Customer $customer */
$customer = Bootstrap::getObjectManager()->create(Customer::class);

$customer->setWebsiteId(1)
    ->setId(1)
    ->setEmail('customer@example.com')
    ->setPassword('password')
    ->setGroupId(1)
    ->setStoreId(1)
    ->setIsActive(1)
    ->setPrefix('Mr.')
    ->setFirstname('John')
    ->setMiddlename('A')
    ->setLastname('Smith')
    ->setDefaultBilling(1)
    ->setDefaultShipping(1)
    ->setSuffix('Esq.')
    ->setTaxvat('12')
    ->setGender(0);

/** @var \Magento\Customer\Model\Data\Customer $customerDataModel */
$customerDataModel = $customer->getDataModel();
$customerDataModel->setCustomAttribute('quickbooks_sync_status', 1);
$customerDataModel->setCustomAttribute('quickbooks_id', 2);
$customerDataModel->setCustomAttribute('quickbooks_sync_token', 3);

$customer->isObjectNew(true);
$customer->updateData($customerDataModel);
$customer->save();
