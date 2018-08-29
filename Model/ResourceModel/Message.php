<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\QuickbooksBasic\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Message extends AbstractDb
{
    /**
     * @var \TNW\QuickbooksBasic\Model\Config
     */
    private $quickbooksConfig;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    private $dataObjectFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Message constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \TNW\QuickbooksBasic\Model\Config $quickbooksConfig
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param string|null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \TNW\QuickbooksBasic\Model\Config $quickbooksConfig,
        \Magento\Framework\DataObject\Factory $dataObjectFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->quickbooksConfig = $quickbooksConfig;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('tnw_quickbooks_message', 'message_id');
    }

    /**
     * @param string $uid
     * @param int $level
     * @param string $message
     * @param int|null $websiteId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveRecord($uid, $level, $message, $websiteId = null)
    {
        $object = $this->dataObjectFactory->create([
            'transaction_uid' => $uid,
            'level' => $level,
            'message' => $message,
            'website_id' => $this->storeManager->getWebsite($websiteId)->getId()
        ]);

        $this->getConnection()
            ->insert(
                $this->getMainTable(),
                $this->_prepareDataForTable($object, $this->getMainTable())
            );
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function clearLast()
    {
        $count = $this->getConnection()
            ->fetchOne("SELECT COUNT(*) FROM `{$this->getMainTable()}`");

        $dbLogLimit = $this->quickbooksConfig->getDbLogLimit();
        if ($count > $dbLogLimit) {
            $limit = $count - $dbLogLimit;
            $this->getConnection()
                ->query("DELETE FROM `{$this->getMainTable()}` ORDER BY `{$this->getIdFieldName()}` ASC LIMIT {$limit}");
        }
    }
}
