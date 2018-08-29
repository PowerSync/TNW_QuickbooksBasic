<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic;

use Magento\Config\Model\Config\Factory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use TNW\QuickbooksBasic\Model\Quickbooks;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;

/**
 * Class TokenData
 *
 * @package TNW\QuickbooksBasic
 */
class TokenData
{
    const ACCESS_TOKEN_LIFE_TIME_IN_DAYS = 180;
    const ACCESS_TOKEN_PERIOD_TO_RENEW = 29;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $config;

    /** @var \Magento\Config\Model\Config\Factory */
    private $configFactory;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @param ScopeConfigInterface $config
     * @param Factory $configFactory
     * @param ResourceConfig $resourceConfig
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        ScopeConfigInterface $config,
        Factory $configFactory,
        ResourceConfig $resourceConfig,
        TypeListInterface $cacheTypeList
    ) {
        $this->config = $config;
        $this->configFactory = $configFactory;
        $this->resourceConfig = $resourceConfig;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @return \Zend_Oauth_Token_Access | null
     */
    public function getAccessToken()
    {
        if ($this->isAccessTokenExpired()) {
            return null;
        }
        $serializedToken = $this->config->getValue(
            Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS
        );

        return \unserialize($serializedToken);
    }

    /**
     * @return bool
     */
    public function isAccessTokenExpired()
    {
        $lastDate = $this->getAccessTokenLastDate();

        if (!$lastDate) {
            return true;
        }

        $lastDate = date_create($this->getAccessTokenLastDate());
        $now = date_create();
        /** @var /DateInterval $interval */
        $interval = date_diff($lastDate, $now);

        return $interval->format('%a') >= self::ACCESS_TOKEN_LIFE_TIME_IN_DAYS;
    }

    /**
     * @return string
     */
    public function getAccessTokenLastDate()
    {
        return $this->config->getValue(
            Quickbooks::XML_PATH_QUICKBOOKS_DATE_LAST_TIME_GET_DATA_TOKEN_ACCESS
        );
    }

    /**
     * @return bool
     */
    public function isAccessTokenNeedRenewal()
    {
        $lastDate = $this->getAccessTokenLastDate();
        if (!$lastDate) {
            return false;
        }
        $lastDate = date_create($this->getAccessTokenLastDate());
        $now = date_create();
        /** @var /DateInterval $interval */
        $interval = date_diff($lastDate, $now);
        $accessTokenLifeTimeInDays = $interval->format('%a');
        $result =
            $accessTokenLifeTimeInDays >=
            self::ACCESS_TOKEN_LIFE_TIME_IN_DAYS -
            self::ACCESS_TOKEN_PERIOD_TO_RENEW &&
            $accessTokenLifeTimeInDays < self::ACCESS_TOKEN_LIFE_TIME_IN_DAYS;

        return $result;
    }

    /**
     * @param \Zend_Oauth_Token_Access $token
     * @return $this
     * @throws \Exception
     */
    public function setAccessToken(\Zend_Oauth_Token_Access $token)
    {
        $serializedToken = \serialize($token);
        $this->resourceConfig->saveConfig(
            Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS,
            $serializedToken,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->resourceConfig->saveConfig(
            Quickbooks::XML_PATH_QUICKBOOKS_DATE_LAST_TIME_GET_DATA_TOKEN_ACCESS,
            date('Y-m-d'),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->cacheTypeList->cleanType('config');
        return $this;
    }

    /**
     * Clear access token from database
     *
     * @return $this
     * @throws \Exception
     */
    public function clearAccessToken()
    {
        $this->resourceConfig->saveConfig(
            Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS,
            null,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->resourceConfig->saveConfig(
            Quickbooks::XML_PATH_QUICKBOOKS_DATE_LAST_TIME_GET_DATA_TOKEN_ACCESS,
            null,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->cacheTypeList->cleanType('config');
        return $this;
    }

    /**
     * @return \Zend_Oauth_Token_Request
     */
    public function getRequestToken()
    {
        $serializedToken = $this->config->getValue(
            Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_REQUEST
        );

        return \unserialize($serializedToken);
    }

    /**
     * @param \Zend_Oauth_Token_Request $token
     * @return $this
     * @throws \Exception
     */
    public function setRequestToken(\Zend_Oauth_Token_Request $token = null)
    {
        $serializedToken = \serialize($token);
        $this->resourceConfig->saveConfig(
            Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_REQUEST,
            $serializedToken,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->cacheTypeList->cleanType('config');
        return $this;
    }

    /**
     * @param $config
     * @return \Zend_Oauth_Consumer
     */
    public function getConsumer($config)
    {
        return new \Zend_Oauth_Consumer($config);
    }

    /**
     * @param string $companyId
     * @return $this
     * @throws \Exception
     */
    public function setCompanyId($companyId)
    {
        $this->resourceConfig->saveConfig(
            Quickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID,
            $companyId,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->cacheTypeList->cleanType('config');
        return $this;
    }
}
