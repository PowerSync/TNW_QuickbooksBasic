<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\QuickbooksBasic\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Token - used for data manipulations with token data for QBO
 */
class Token extends AbstractDb
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('tnw_quickbooks_token', 'token_id');
    }

    /**
     * @param $tokenData
     * @param $expirationDate
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveRecord($tokenData, $expirationDate)
    {
        if (($this->isJson($tokenData)
            || $this->serializer->unserialize($tokenData) instanceof \OAuth\OAuth2\Token\StdOAuth2Token)
            && $this->isDate($expirationDate)
        ) {
            return $this->getConnection()
                ->insert(
                    $this->getMainTable(),
                    [
                        'value' => $tokenData,
                        'expires' => $expirationDate
                    ]
                );
        } else {
            return 0;
        }
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getLastRecord()
    {
        $select = $this
            ->getConnection()
            ->select()
            ->from($this->getMainTable())
            ->order($this->getIdFieldName() . ' DESC')
            ->limit(1);
        return $this->getConnection()->fetchRow($select);
    }

    /**
     * @param $id
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($id)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable()
        )->where(
            $this->getIdFieldName() . ' =?',
            (int) $id
        );
        return $this->getConnection()->fetchRow($select);
    }

    /**
     * @param $id
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id)
    {
        $where = ['token_id = ?' => (int) $id];
        return $this->getConnection()->delete($this->getMainTable(), $where);
    }

    /**
     * @param $value
     * @return bool
     */
    private function isJson($value)
    {
        if ($value === '') {
            return false;
        }

        \json_decode($value);
        if (\json_last_error()) {
            return false;
        }

        return true;
    }

    /**
     * @param $value
     * @return bool
     */
    private function isDate($value)
    {
        if (!$value) {
            return false;
        }
        try {
            new \DateTime($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
