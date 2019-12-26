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
     * @var \OAuth\Common\Consumer\CredentialsFactory
     */
    private $credentialsFactory;

    /**
     * TokenData constructor.
     * @param ScopeConfigInterface $config
     * @param Factory $configFactory
     * @param ResourceConfig $resourceConfig
     * @param TypeListInterface $cacheTypeList
     * @param \OAuth\Common\Consumer\CredentialsFactory $credentialsFactory
     */
    public function __construct(
        ScopeConfigInterface $config,
        Factory $configFactory,
        ResourceConfig $resourceConfig,
        TypeListInterface $cacheTypeList,
        \OAuth\Common\Consumer\CredentialsFactory $credentialsFactory
    ) {
        $this->credentialsFactory = $credentialsFactory;
        $this->config = $config;
        $this->configFactory = $configFactory;
        $this->resourceConfig = $resourceConfig;
        $this->cacheTypeList = $cacheTypeList;
    }

    public function getAccessToken()
    {
        if ($this->isAccessTokenExpired()) {
            return null;
        }
        $serializedToken = $this->config->getValue(
            Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS
        );
        $result = \unserialize($serializedToken);
        if ($result instanceof \Zend_Oauth_Token_Access) {
            $this->clearAccessToken();
            $result = null;
        }
        return $result;
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
     * @param $token
     * @return $this
     */
    public function setAccessToken($token)
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
     * @return $this
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
     * @return mixed
     */
    public function getAuthTokenState()
    {
        return $this->config->getValue(
            Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_AUTH
        );
    }

    /**
     * @param $state
     * @return $this
     */
    public function setAuthTokenState($state)
    {
        $this->resourceConfig->saveConfig(
            Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_AUTH,
            $state,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->cacheTypeList->cleanType('config');
        return $this;
    }

    /**
     * @param $config
     * @return \OAuth\Common\Consumer\Credentials
     */
    public function getConsumer($config)
    {
        return $this->credentialsFactory->create($config);
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
