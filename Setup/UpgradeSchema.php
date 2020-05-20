<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer;

/**
 * @codeCoverageIgnore
 * Class UpgradeSchema
 * @package TNW\QuickbooksBasic\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.0.2') < 0) {
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
        }

        if (version_compare($context->getVersion(), '2.0.3') < 0) {
            if ($setup->getConnection()->tableColumnExists(
                $setup->getTable(Customer::CUSTOMER_QUICKBOOKS_TABLE),
                'quickbooks_id'
            )
            ) {
                $setup->getConnection()->dropColumn(
                    $setup->getTable(Customer::CUSTOMER_QUICKBOOKS_TABLE),
                    'quickbooks_id'
                );
            }
            if ($setup->getConnection()->tableColumnExists(
                $setup->getTable(Customer::CUSTOMER_QUICKBOOKS_TABLE),
                'quickbooks_sync'
            )
            ) {
                $setup->getConnection()->dropColumn(
                    $setup->getTable(Customer::CUSTOMER_QUICKBOOKS_TABLE),
                    'quickbooks_sync'
                );
            }
            if ($setup->getConnection()->tableColumnExists(
                $setup->getTable('customer_address_entity'),
                'quickbooks_id'
            )
            ) {
                $setup->getConnection()->dropColumn(
                    $setup->getTable('customer_address_entity'),
                    'quickbooks_id'
                );
            }
        }

        if (version_compare($context->getVersion(), '2.0.19') < 0) {
            $setup->getConnection()->modifyColumn(
                $setup->getTable(Customer::SHIPPINGG_ADDRESS_QUICKBOOKS_TABLE),
                Customer::ADDRESS_ENTITY_ID,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'type' => Table::TYPE_INTEGER,
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable(Customer::SHIPPINGG_ADDRESS_QUICKBOOKS_TABLE),
                Customer::QUICKBOOKS_ID,
                [
                    'unsigned' => true,
                    'nullable' => false,
                    'type' => Table::TYPE_INTEGER,
                ]
            );
            $setup->getConnection()->modifyColumn(
                $setup->getTable(Customer::BILLING_ADDRESS_QUICKBOOKS_TABLE),
                Customer::ADDRESS_ENTITY_ID,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'type' => Table::TYPE_INTEGER,
                ]
            );
            $setup->getConnection()->modifyColumn(
                $setup->getTable(Customer::BILLING_ADDRESS_QUICKBOOKS_TABLE),
                Customer::QUICKBOOKS_ID,
                [
                    'unsigned' => true,
                    'nullable' => false,
                    'type' => Table::TYPE_INTEGER,
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.1.15') < 0) {
            $this->addSystemMessageTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function addSystemMessageTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('tnw_quickbooks_message'))
            ->addColumn('message_id', Table::TYPE_BIGINT, null, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ], 'Log ID')
            ->addColumn('transaction_uid', Table::TYPE_TEXT, 64, [
                'nullable' => true,
                'default' => null
            ], 'Transaction')
            ->addColumn('level', Table::TYPE_SMALLINT, null, [
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
            ], 'Level')
            ->addColumn('website_id', Table::TYPE_SMALLINT, null, [
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
            ], 'Website')
            ->addColumn('message', Table::TYPE_TEXT, '64k', [
                'nullable' => true,
                'default' => null
            ], 'Message')
            ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT
            ], 'Create At')
            ->addIndex(
                $setup->getIdxName('tnw_quickbooks_message', ['website_id']),
                ['website_id']
            )
            ->addForeignKey(
                $setup->getFkName('tnw_quickbooks_message', 'website_id', 'store_website', 'website_id'),
                'website_id', $setup->getTable('store_website'), 'website_id', Table::ACTION_CASCADE
            )
        ;

        $setup->getConnection()
            ->createTable($table);
    }
}
