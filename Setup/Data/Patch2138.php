<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\QuickbooksBasic\Setup\Data;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use TNW\QuickbooksBasic\Model\Quickbooks;
use TNW\QuickbooksBasic\Model\ResourceModel\TokenFactory;

class Patch2138 implements DataPatchInterface, PatchRevertableInterface, PatchVersionInterface
{
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \TNW\QuickbooksBasic\Model\ResourceModel\TokenFactory
     */
    private $tokenFactory;

    /**
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \TNW\QuickbooksBasic\Model\ResourceModel\TokenFactory $tokenFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        TokenFactory             $tokenFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->tokenFactory = $tokenFactory;
    }

    public static function getVersion()
    {
        return '2.1.38';
    }

    public static function getDependencies()
    {
        return [
            Patch2118::class,
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

        $setup->endSetup();
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
