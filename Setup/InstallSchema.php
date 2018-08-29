<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer;

/**
 * @codeCoverageIgnore
 * Class InstallSchema
 * @package TNW\QuickbooksBasic\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (!$setup->tableExists(Customer::BILLING_ADDRESS_QUICKBOOKS_TABLE)) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable(Customer::BILLING_ADDRESS_QUICKBOOKS_TABLE)
            )
                ->addColumn(
                    Customer::ADDRESS_ENTITY_ID,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'unsigned' => true,
                        'primary' => true,
                        'nullable' => false,
                    ],
                    'Address ID'
                )->addColumn(
                    Customer::QUICKBOOKS_ID,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => true,
                    ],
                    'QuickBooks ID'
                );

            $setup->getConnection()->createTable($table);
        }

        if (!$setup->tableExists(Customer::SHIPPINGG_ADDRESS_QUICKBOOKS_TABLE)) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable(Customer::SHIPPINGG_ADDRESS_QUICKBOOKS_TABLE)
            )
                ->addColumn(
                    Customer::ADDRESS_ENTITY_ID,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'unsigned' => true,
                        'primary' => true,
                        'nullable' => false,
                    ],
                    'Address ID'
                )->addColumn(
                    Customer::QUICKBOOKS_ID,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => true,
                    ],
                    'QuickBooks ID'
                );

            $setup->getConnection()->createTable($table);
        }

        $setup->endSetup();
    }
}
