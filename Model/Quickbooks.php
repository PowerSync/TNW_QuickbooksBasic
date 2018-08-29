<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Model;

use Magento\Config\Model\Config\Factory as ConfigFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use TNW\QuickbooksBasic\Model\Config as QuickbooksConfig;
use TNW\QuickbooksBasic\Service\Quickbooks as QuickbooksService;

/**
 * Class Quickbooks
 *
 * @package TNW\QuickbooksBasic\Model
 */
class Quickbooks
{
    /** @codingStandardsIgnoreStart */
    const XML_PATH_QUICKBOOKS_GENERAL_ACTIVE = 'quickbooks/general/active';
    const XML_PATH_QUICKBOOKS_GENERAL_URL_TOKEN_REQUEST = 'quickbooks/general/url_token_request'; //hardcoded in Config.php
    const XML_PATH_QUICKBOOKS_GENERAL_URL_TOKEN_ACCESS = 'quickbooks/general/url_token_access';   //hardcoded in Config.php
    const XML_PATH_QUICKBOOKS_GENERAL_URL_USER_AUTH = 'quickbooks/general/url_user_auth';         //hardcoded in Config.php
    const XML_PATH_QUICKBOOKS_GENERAL_URL_QUICKBOOKS_API = 'quickbooks/general/url_api_quickbooks';
    const XML_PATH_QUICKBOOKS_GENERAL_URL_QUICKBOOKS_RECONNECT = 'quickbooks/general/url_reconnect';
    const XML_PATH_QUICKBOOKS_GENERAL_CONSUMER_KEY = 'quickbooks/general/cosumer_key';
    const XML_PATH_QUICKBOOKS_GENERAL_CONSUMER_SECRET = 'quickbooks/general/cosumer_secret';
    const XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID = 'quickbooks/general/company_id';
    const XML_PATH_QUICKBOOKS_GENERAL_TIMEZONE_FOR_DATE = 'quickbooks/general/timezone_for_date';

    const XML_PATH_QUICKBOOKS_DATA_TOKEN_REQUEST = 'quickbooks/data/token_request';
    const XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS = 'quickbooks/data/token_access';
    const XML_PATH_QUICKBOOKS_DATE_LAST_TIME_GET_DATA_TOKEN_ACCESS = 'quickbooks/data/token_access_last_date';

    const XML_PATH_QUICKBOOKS_ENVIRONMENT = 'quickbooks/general/environment';
    /** @codingStandardsIgnoreEnd */
    
    const PROTOCOL = 'https://';
    const QUICKBOOKS_URL = 'quickbooks/general/quickbooks_url';

    const API_QUERY = 'company/:companyId/query';

    const BATCH_ITEM_REQUEST_NAME = 'BatchItemRequest';
    const BATCH_ITEM_RESPONSE = 'BatchItemResponse';
    const B_ID = 'bId';
    const BATCH_FAULT = 'Fault';
    const BATCH_CHUNK_SIZE = 30;
    const BATCH_OBJECT_NAME = 'OBJECT_NAME';
    const BATCH_API_URL = 'company/:companyId/batch?minorversion=6';

    /** @var ScopeConfigInterface */
    protected $config;

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var EncoderInterface */
    protected $jsonEncoder;

    /** @var DecoderInterface */
    protected $jsonDecoder;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Config */
    protected $quickbooksConfig;

    /** @var QuickbooksService */
    protected $quickbooksService;

    /** @var ConfigFactory */
    protected $configFactory;

    /**
     * Quickbooks constructor.
     *
     * @param ConfigFactory        $configFactory
     * @param ScopeConfigInterface $config
     * @param UrlInterface         $urlBuilder
     * @param EncoderInterface     $jsonEncoder
     * @param DecoderInterface     $jsonDecoder
     * @param LoggerInterface      $logger
     * @param Config               $quickbooksConfig
     * @param QuickbooksService    $quickbooksService
     */
    public function __construct(
        ConfigFactory $configFactory,
        ScopeConfigInterface $config,
        UrlInterface $urlBuilder,
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        LoggerInterface $logger,
        QuickbooksConfig $quickbooksConfig,
        QuickbooksService $quickbooksService
    ) {
        $this->configFactory = $configFactory;
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
        $this->jsonEncoder = $jsonEncoder;
        $this->jsonDecoder = $jsonDecoder;
        $this->logger = $logger;
        $this->quickbooksConfig = $quickbooksConfig;
        $this->quickbooksService = $quickbooksService;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->quickbooksConfig->getConfig();
    }

    /**
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->quickbooksConfig->getIsActive();
    }

    /**
     * @param \DateTime $dateTime
     * @return \DateTime
     */
    public function convertDate(\DateTime $dateTime)
    {
        return $dateTime->setTimezone(timezone_open($this->quickbooksConfig->getTimezoneForDate()));
    }

    /**
     * @return string
     */
    public function getRequestTokenUrl()
    {
        return $this->quickbooksService->getRequestTokenUrl();
    }

    /**
     * @return \Zend_Oauth_Token_Request
     */
    public function getRequestToken()
    {
        return $this->quickbooksService->getRequestToken();
    }

    /**
     * @param \Zend_Oauth_Token_Request $token
     *
     * @return $this
     */
    public function setRequestToken(\Zend_Oauth_Token_Request $token)
    {
        $this->quickbooksService->setRequestToken($token);

        return $this;
    }

    /**
     * @return null|\Zend_Oauth_Token_Access
     */
    public function getAccessToken()
    {
        return $this->quickbooksService->getAccessToken();
    }

    /**
     * @return bool
     */
    public function isAccessTokenNeedRenewal()
    {
        return $this->quickbooksService->isAccessTokenNeedRenewal();
    }

    /**
     * @return QuickbooksService
     */
    public function getQuickbooksService()
    {
        return $this->quickbooksService;
    }

    /**
     * @param \Zend_Oauth_Token_Access $token
     *
     * @return $this
     */
    public function setAccessToken(\Zend_Oauth_Token_Access $token)
    {
        $this->quickbooksService->setAccessToken($token);

        return $this;
    }

    /**
     * @param RequestInterface $request
     *
     * @return $this
     */
    public function grant(RequestInterface $request)
    {
        $this->quickbooksService->grant($request);

        return $this;
    }

    /**
     * @return array
     */
    public function reconnect()
    {
        return $this->quickbooksService->reconnect();
    }

    /**
     * Disconnecting from QuickBooks
     */
    public function disconnect()
    {
        return $this->quickbooksService->disconnect();
    }

    /**
     * @param string $queryString
     *
     * @return \Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function query($queryString)
    {
        return $this->quickbooksService->query($queryString);
    }


    public function throwQuickbooksException(\Exception $e)
    {
        $this->logger->error($e->getMessage());
        throw $e;
    }

    /**
     * Add error
     * @param array $entityIdBIdAssoc
     * @param array $errors
     * @param string|null $message
     * @return array
     */
    protected function handleBatchError($entityIdBIdAssoc, $errors, $message = null)
    {
        $message = $message ?: __('Invalid Data Sended to quickbooks');

        foreach ($entityIdBIdAssoc as $object) {
            $errors[] = [
                'message' => $message,
                'object' => $object,
            ];
        }

        return $errors;
    }

    /**
     * Add error
     * @param array $fault
     * @param object $object
     * @return array
     */
    protected function addErrorFromBatchItem($fault, $object)
    {
        $message = isset($fault['Error'][0]['Message'])
            ? $fault['Error'][0]['Message']
            : "";

        $detail = isset($fault['Error'][0]['Detail'])
            ? $fault['Error'][0]['Detail']
            : "";

        $error = [
            'message' => $message.' Detail:'.$detail,
            'object' => $object
        ];

        return $error;
    }
}
