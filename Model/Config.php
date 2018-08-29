<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;

/**
 * Class Config
 *
 * @package TNW\QuickbooksBasic\Model
 */
class Config
{
    const IN_SYNC = 'In Sync';
    const OUT_OF_SYNC = 'Out of Sync';

    /** @var ScopeConfigInterface */
    protected $config;

    /** @var UrlInterface */
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
    protected $quickbooksConsumerKeys = [
        0 => 'qyprdifTfuliLIuzNMSDplyxNWqdaB',
        1 => 'qyprdBr7giY6Bab1B0eorSQzf9aPOs'
    ];

    /** @var array  */
    protected $quickbooksConsumerSecrets = [
        0 => 'hTxmA3YWSjf3rUqHfidVaYVTkT4XKP27w8nfpQlk',
        1 => '9mqSkQLJY4mPsZRM8n4uL9FfTn226Was2Rl1x5Zc'
    ];

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $config
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        ScopeConfigInterface $config,
        UrlInterface $urlBuilder
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
            'requestTokenUrl' =>
                'https://oauth.intuit.com/oauth/v1/get_request_token',
            'accessTokenUrl' =>
                'https://oauth.intuit.com/oauth/v1/get_access_token',
            'userAuthorizationUrl' =>
                'https://appcenter.intuit.com/Connect/Begin',
            'consumerKey' =>
                $this->getQuickbooksConsumerKeyByType($environmentType),
            'consumerSecret' =>
                $this->getQuickbooksConsumerSecretByType($environmentType),
            'callbackUrl' =>
                $this->urlBuilder->getUrl('quickbooks/callback')
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
    public function getQuickbooksConsumerKeyByType($type)
    {
        //use production Consumer Key as default
        $result = $this->quickbooksConsumerKeys[0];

        if (!empty($this->quickbooksConsumerKeys[$type])) {
            $result = $this->quickbooksConsumerKeys[$type];
        }

        return $result;
    }

    /**
     * @param $type int
     * @return string
     */
    public function getQuickbooksConsumerSecretByType($type)
    {
        //use production Consumer Secret as default
        $result = $this->quickbooksConsumerSecrets[0];

        if (!empty($this->quickbooksConsumerSecrets[$type])) {
            $result = $this->quickbooksConsumerSecrets[$type];
        }

        return $result;
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
