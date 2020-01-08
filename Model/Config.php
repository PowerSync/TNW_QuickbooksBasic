<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 *
 * @package TNW\QuickbooksBasic\Model
 */
class Config
{
    /**
     * Consts Block
     */
    const IN_SYNC = 'In Sync';
    const OUT_OF_SYNC = 'Out of Sync';
    const AUTH_URL = 'https://appcenter.intuit.com/connect/oauth2';
    const ACCESS_TOKEN_URL = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    const DISCONNECT_TOKEN_URL = 'https://developer.api.intuit.com/v2/oauth2/tokens/revoke';
    const CALLBACK_ROUTE_PATH = 'tnw-quickbooks/callback';

    /** @var ScopeConfigInterface */
    protected $config;

    /**
     * @var \Magento\Framework\Url
     */
    protected $urlBuilder;

    /** @var array */
    protected $quickbooksUrls = [
        0 => 'qbo.intuit.com',
        1 => 'sandbox.qbo.intuit.com'
    ];

    /** @var array */
    protected $quickbooksApiUrls = [
        0 => 'https://quickbooks.api.intuit.com/v3/',
        1 => 'https://sandbox-quickbooks.api.intuit.com/v3/'
    ];

    /** @var array  */
    protected $quickbooksClientId = [
        0 => 'Q0pOMVz6YEAORni1VGs3vfR7Kn55WD5aRUHsWWOKn6q82WFUH3',
        1 => 'ABNBe35SCjcVAGmTReHHMDSEtRwi5f5YRdmGPga6wOOh3iMojI'
    ];

    /** @var array  */
    protected $quickbooksClientSecret = [
        0 => '5wd3iJmo6dMWbrhy3dZXSaZuOI1RdAxPkuHKGHRG',
        1 => 'I2JnVPpGKVnU5BfOz8y1qr0yGAFXLRzP8DvAtkJm'
    ];

    /**
     * Config constructor.
     * @param ScopeConfigInterface $config
     * @param \Magento\Framework\Url $urlBuilder
     */
    public function __construct(
        ScopeConfigInterface $config,
        \Magento\Framework\Url $urlBuilder
    ) {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $environmentType = (int)$this->config->getValue(
            Quickbooks::XML_PATH_QUICKBOOKS_ENVIRONMENT
        );

        return [
            'response_type' => 'code',
            'client_id' =>  $this->getQuickbooksClientIdByType($environmentType),
            'client_secret' => $this->getQuickbooksClientSecretByType($environmentType),
            'redirect_uri' => $this->urlBuilder->getUrl(
                \TNW\QuickbooksBasic\Model\Config::CALLBACK_ROUTE_PATH,
                ['_nosid' => true]
            ),
            'scope' => 'com.intuit.quickbooks.accounting openid email profile',
        ];
    }

    /**
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->config->isSetFlag(
            Quickbooks::XML_PATH_QUICKBOOKS_GENERAL_ACTIVE
        );
    }

    /**
     * @return mixed
     */
    public function getTimezoneForDate()
    {
        return $this->config->getValue(Quickbooks::XML_PATH_QUICKBOOKS_GENERAL_TIMEZONE_FOR_DATE);
    }

    /**
     * @param $type int
     * @return string
     */
    public function getQuickbooksUrlByType($type)
    {
        //use production url as default
        $result = $this->quickbooksUrls[0];

        if (!empty($this->quickbooksUrls[$type])) {
            $result = $this->quickbooksUrls[$type];
        }

        return $result;
    }

    /**
     * @param $type int
     * @return string
     */
    public function getQuickbooksApiUrlByType($type)
    {
        //use production api url as default
        $result = $this->quickbooksApiUrls[0];

        if (!empty($this->quickbooksApiUrls[$type])) {
            $result = $this->quickbooksApiUrls[$type];
        }

        return $result;
    }

    /**
     * @param $type int
     * @return string
     */
    public function getQuickbooksClientIdByType($type)
    {
        return $this->config->getValue('quickbooks/general/client_id');
    }

    /**
     * @param $type int
     * @return string
     */
    public function getQuickbooksClientSecretByType($type)
    {
        return $this->config->getValue('quickbooks/general/client_secret');
    }

    /**
     * @return bool
     */
    public function getLogStatus()
    {
        return $this->config->isSetFlag('quickbooks/advanced/log_status');
    }

    /**
     * @return bool
     */
    public function getDbLogStatus()
    {
        return $this->config->isSetFlag('quickbooks/advanced/db_log_status');
    }

    /**
     * @return int
     */
    public function getDbLogLimit()
    {
        return (int)$this->config->getValue('quickbooks/advanced/db_log_limit');
    }
}
