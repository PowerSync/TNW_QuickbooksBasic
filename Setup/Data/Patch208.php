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

class Patch208 implements DataPatchInterface, PatchRevertableInterface, PatchVersionInterface
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
        return '2.0.8';
    }

    public static function getDependencies()
    {
        return [
            Patch201::class,
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
        $quickbooksSetup = $this->quickbooksSetupFactory->create(
            ['setup' => $setup]
        );

        /**
         * Change customer attributes
         */
        $quickbooksSetup->updateAttribute(
            Customer::ENTITY,
            'quickbooks_id',
            'frontend_label',
            'QuickBooks Id'
        );

        $quickbooksSetup->updateAttribute(
            Customer::ENTITY,
            'quickbooks_sync_token',
            'frontend_label',
            'QuickBooks Sync Token'
        );

        $setup->endSetup();
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
