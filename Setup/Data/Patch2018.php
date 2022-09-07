<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\QuickbooksBasic\Setup\Data;

use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class Patch2018 implements DataPatchInterface, PatchRevertableInterface, PatchVersionInterface
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
        return '2.0.18';
    }

    public static function getDependencies()
    {
        return [
            Patch208::class,
        ];
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
        $quickbooksSetup = $this->quickbooksSetupFactory->create(['setup' => $setup]);
        $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_id', 'is_used_in_grid', false);
        $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_id', 'is_searchable_in_grid', false);

        $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_sync_token', 'is_used_in_grid', false);
        $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_sync_token', 'is_searchable_in_grid', false);

        $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_sync_status', 'is_used_in_grid', false);
        $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_sync_status', 'is_searchable_in_grid', false);

        $setup->endSetup();
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
