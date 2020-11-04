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
use \Zend\Serializer\Serializer;
use TNW\QuickbooksBasic\Model\ResourceModel\TokenFactory;
use OAuth\Common\Consumer\CredentialsFactory;

/**
 * Class TokenData
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
     * @var CredentialsFactory
     */
    private $credentialsFactory;

    /**
     * @var string
     */
    private $currentAccessTokenValue = '';

    /**
     * @var string
     */
    private $currentAccessTokenDate = '';

    /**
     * @var Model\ResourceModel\Token
     */
    private $tokenFactory;

    /**
     * TokenData constructor.
     * @param ScopeConfigInterface $config
     * @param Factory $configFactory
     * @param ResourceConfig $resourceConfig
     * @param TypeListInterface $cacheTypeList
     * @param CredentialsFactory $credentialsFactory
     * @param TokenFactory $tokenFactory
     */
    public function __construct(
        ScopeConfigInterface $config,
        Factory $configFactory,
        ResourceConfig $resourceConfig,
        TypeListInterface $cacheTypeList,
        CredentialsFactory $credentialsFactory,
        TokenFactory $tokenFactory
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->credentialsFactory = $credentialsFactory;
        $this->config = $config;
        $this->configFactory = $configFactory;
        $this->resourceConfig = $resourceConfig;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @return mixed|null
     * @throws \Zend_Json_Exception
     */
    public function getAccessToken()
    {
        if ($this->isAccessTokenExpired()) {
            return null;
        }
        $tokenId = $this->config->getValue(
            Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS
        );
        if (!$this->currentAccessTokenValue) {
            $storedToken = $this->tokenFactory->create()->getById($tokenId);
            if (!$this->currentAccessTokenValue && isset($storedToken['value'])) {
                $this->currentAccessTokenValue = $storedToken['value'];
            }
        }
        if ($this->isJson($this->currentAccessTokenValue)) {
            $result = \Zend_Json::decode($this->currentAccessTokenValue, \Zend_Json::TYPE_ARRAY);
        } elseif ($this->currentAccessTokenValue) {
            $result = Serializer::unserialize($this->currentAccessTokenValue);
        } else {
            $result = null;
        }

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
        $tokenId = $this->config->getValue(
            Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS
        );
        if (!$this->currentAccessTokenDate && $tokenId) {
            $storedToken = $this->tokenFactory->create()->getById($tokenId);
            if (!$this->currentAccessTokenValue && isset($storedToken['value'])) {
                $this->currentAccessTokenValue = $storedToken['value'];
            }
            if ($storedToken && isset($storedToken['expires'])) {
                $this->currentAccessTokenDate = $storedToken['expires'];
            }
        }
        return $this->currentAccessTokenDate;
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
        $this->clearAccessToken();
        $serializedToken = \serialize($token);
        $tokenModel = $this->tokenFactory->create();
        $tokenModel->saveRecord(
            $serializedToken,
            date('Y-m-d')
        );
        $tokenRecord = $tokenModel->getLastRecord();
        if (isset($tokenRecord['token_id'])) {
            $this->resourceConfig->saveConfig(
                Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS,
                $tokenRecord['token_id'],
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
            $this->currentAccessTokenDate = date('Y-m-d');
            $this->resourceConfig->saveConfig(
                Quickbooks::XML_PATH_QUICKBOOKS_DATE_LAST_TIME_GET_DATA_TOKEN_ACCESS,
                $this->currentAccessTokenDate,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
            $this->currentAccessTokenValue = $serializedToken;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function clearAccessToken()
    {
        $this->currentAccessTokenValue = null;
        $tokenId = $this->config->getValue(
            Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS
        );
        if ($tokenId) {
            $this->tokenFactory->create()->deleteById($tokenId);
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
        }
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
}
