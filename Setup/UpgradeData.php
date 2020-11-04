<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Setup;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use TNW\QuickbooksBasic\Model\ResourceModel\TokenFactory;
use TNW\QuickbooksBasic\Model\Quickbooks;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class UpgradeData
 */
class UpgradeData implements UpgradeDataInterface
{
    /** @var QuickbooksSetupFactory */
    protected $quickbooksSetupFactory;

    /** @var IndexerRegistry */
    protected $indexerRegistry;

    /** @var Config */
    protected $eavConfig;

    /**
     * @var TokenFactory
     */
    protected $tokenFactory;

    /**
     * UpgradeData constructor.
     * @param QuickbooksSetupFactory $quickbooksSetupFactory
     * @param IndexerRegistry $indexerRegistry
     * @param Config $eavConfig
     * @param TokenFactory $tokenFactory
     */
    public function __construct(
        QuickbooksSetupFactory $quickbooksSetupFactory,
        IndexerRegistry $indexerRegistry,
        Config $eavConfig,
        TokenFactory $tokenFactory
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->quickbooksSetupFactory = $quickbooksSetupFactory;
        $this->indexerRegistry = $indexerRegistry;
        $this->eavConfig = $eavConfig;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '2.0.1') < 0) {

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
        }

        if (version_compare($context->getVersion(), '2.0.8') < 0) {
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
        }

        if (version_compare($context->getVersion(), '2.0.18') < 0) {
            /** @var QuickbooksSetup $quickbooksSetup */
            $quickbooksSetup = $this->quickbooksSetupFactory->create(['setup' => $setup]);
            $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_id', 'is_used_in_grid', false);
            $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_id', 'is_searchable_in_grid', false);

            $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_sync_token', 'is_used_in_grid', false);
            $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_sync_token', 'is_searchable_in_grid', false);

            $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_sync_status', 'is_used_in_grid', false);
            $quickbooksSetup->updateAttribute(Customer::ENTITY, 'quickbooks_sync_status', 'is_searchable_in_grid', false);
        }

        if (version_compare($context->getVersion(), '2.1.18') < 0) {
            $this->version2118($context, $setup);
        }

        if (version_compare($context->getVersion(), '2.1.38') < 0) {
            $this->version2138($context, $setup);
        }

        $setup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    protected function version2118(
        ModuleContextInterface $context,
        ModuleDataSetupInterface $setup
    ) {
        $setup->getConnection()->insert(
            $setup->getTable('core_config_data'),
            [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'tnw_quickbooksbasic/survey/start_date',
                'value' => date_create()->modify('+7 day')->getTimestamp()
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function version2138(
        ModuleContextInterface $context,
        ModuleDataSetupInterface $setup
    ) {
        $select = $setup->getConnection()
            ->select()
            ->from($setup->getTable('core_config_data'))
            ->where(
                'path =?',
                Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS
            );
        $accessToken = $setup->getConnection()->fetchRow($select);

        $select = $setup->getConnection()
            ->select()
            ->from($setup->getTable('core_config_data'))
            ->where(
                'path =?',
                Quickbooks::XML_PATH_QUICKBOOKS_DATE_LAST_TIME_GET_DATA_TOKEN_ACCESS
            );
        $lastDate = $setup->getConnection()->fetchRow($select);

        if (isset($accessToken['value']) && isset($lastDate['value'])) {
            $tokenResourceModel = $this->tokenFactory->create();
            $result = $tokenResourceModel->saveRecord(
                $accessToken['value'],
                $lastDate['value']
            );
            if ($result == 1) {
                $currentRecord = $tokenResourceModel->getLastRecord();
                $setup->getConnection()->insertOnDuplicate(
                    $setup->getTable('core_config_data'),
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS,
                        'value' => $currentRecord['token_id']
                    ]
                );
            }
        }
    }
}
