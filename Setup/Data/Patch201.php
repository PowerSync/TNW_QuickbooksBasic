<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\QuickbooksBasic\Setup\Data;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class Patch201 implements DataPatchInterface, PatchRevertableInterface, PatchVersionInterface
{
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var \TNW\QuickbooksBasic\Setup\Data\QuickbooksSetupFactory
     */
    private $quickbooksSetupFactory;

    /**
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \TNW\QuickbooksBasic\Setup\Data\QuickbooksSetupFactory $quickbooksSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        QuickbooksSetupFactory   $quickbooksSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->quickbooksSetupFactory = $quickbooksSetupFactory;
    }

    public static function getVersion()
    {
        return '2.0.1';
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $setup = $this->moduleDataSetup;
        $setup->startSetup();

        /** @var QuickbooksSetup $quickbooksSetup */
        $quickbooksSetup = $this->quickbooksSetupFactory->create(
            ['setup' => $setup]
        );

        $quickbooksSetup->addAttribute(
            Customer::ENTITY,
            'quickbooks_id',
            [
                'type' => 'varchar',
                'required' => false,
                'sort_order' => 1,
                'visible' => false,
                'system' => false,
                'group' => 'Account Information',
                'default' => null,
                'label' => 'QuickBooks Id',
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true
            ]
        );

        $quickbooksSetup->addAttribute(
            Customer::ENTITY,
            'quickbooks_sync_token',
            [
                'type' => 'varchar',
                'required' => false,
                'sort_order' => 2,
                'visible' => false,
                'system' => false,
                'group' => 'Account Information',
                'default' => null,
                'label' => 'QuickBooks Sync Token',
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false
            ]
        );

        $quickbooksSetup->addAttribute(
            Customer::ENTITY,
            'quickbooks_sync_status',
            [
                'type' => 'int',
                'required' => false,
                'sort_order' => 3,
                'visible' => false,
                'system' => false,
                'group' => 'Account Information',
                'default' => 0,
                'label' => 'Sync Status',
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => false,
                'source_model' => 'TNW\QuickbooksBasic\Model\Customer\Attribute\Source\SyncStatus'
            ]
        );

        $quickbooksSetup->addAttributeGroup(
            Customer::ENTITY,
            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            'QuickBooks'
        );
        $attributeCodes = [
            'quickbooks_id',
            'quickbooks_sync_token',
            'quickbooks_sync_status'
        ];
        foreach ($attributeCodes as $code) {
            $quickbooksSetup->addAttributeToSet(
                Customer::ENTITY,
                CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                'QuickBooks',
                $code
            );
        }

        $setup->endSetup();
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
