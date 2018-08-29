<?php
/**
 *  Copyright © 2016 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use TNW\QuickbooksBasic\Model\Config as QuickbooksConfig;
use TNW\QuickbooksBasic\Model\Quickbooks as ModelQuickbooks;
use TNW\QuickbooksBasic\TokenData;

/**
 * Class Quickbooks
 *
 * @package TNW\QuickbooksBasic\Service
 */
class Quickbooks
{
    const MAX_RESULTS_QUERY_LIMITATION_STRING = ' MAXRESULTS 1000';

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $config;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \TNW\QuickbooksBasic\TokenData */
    protected $tokenData;

    /** @var \TNW\QuickbooksBasic\Model\Config */
    protected $quickbooksConfig;

    /** @var ManagerInterface */
    protected $messenger;

    /** @var DecoderInterface */
    protected $jsonDecoder;

    /** @var Registry */
    protected $registry;

    /** @var State */
    protected $state;

    /**
     * Quickbooks constructor.
     *
     * @param ScopeConfigInterface $config
     * @param LoggerInterface $logger
     * @param TokenData $tokenData
     * @param QuickbooksConfig $quickbooksConfig
     * @param ManagerInterface $messageManager
     * @param DecoderInterface $decoder
     * @param Registry $registry
     * @param State $state
     */
    public function __construct(
        ScopeConfigInterface $config,
        LoggerInterface $logger,
        TokenData $tokenData,
        QuickbooksConfig $quickbooksConfig,
        ManagerInterface $messageManager,
        DecoderInterface $decoder,
        Registry $registry,
        State $state
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->tokenData = $tokenData;
        $this->quickbooksConfig = $quickbooksConfig;
        $this->messenger = $messageManager;
        $this->jsonDecoder = $decoder;
        $this->registry = $registry;
        $this->state = $state;
    }

    /**
     * @param $apiRead
     * @param $quickbooksId
     *
     * @return \Zend_Http_Response
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function read($apiRead, $quickbooksId = 0)
    {
        /** @var string $apiUrl */
        $apiUrl = $this->config->getValue(
            ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_URL_QUICKBOOKS_API
        );

        /** @var string $requestUri */
        $requestUri = str_replace([':companyId', ':entityId'], [
            $this->config->getValue(ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID),
            $quickbooksId
        ], $apiRead);

        /** @var \Zend_Oauth_Token_Access $token */
        $token = $this->getAccessToken();

        if (!$token) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Unknown access token'));
        }

        $urlPart = parse_url($apiUrl . $requestUri);

        $parameters = [];
        if (isset($urlPart['query'])) {
            parse_str($urlPart['query'], $parameters);
        }

        /** @var \Zend_Oauth_Client $client */
        $client = $token->getHttpClient($this->quickbooksConfig->getConfig())
            ->setMethod(\Zend_Http_Client::GET)
            ->setUri("{$urlPart['scheme']}://{$urlPart['host']}{$urlPart['path']}")
            ->setParameterGet($parameters)
            ->setHeaders('Accept', 'application/json');

        /** @var \Zend_Http_Response $response */
        $response = $client->request();

        $this->logger->debug('QUICKBOOKS REQUEST URL:' . $client->getUri());
        $this->logger->debug('QUICKBOOKS REQUEST:' . $client->getLastRequest());
        $this->logger->debug('QUICKBOOKS RESPONSE STATUS:' . $response->getStatus());
        $this->logger->debug('QUICKBOOKS RESPONSE BODY:' . $response->getBody());

        return $response;
    }

    /**
     * @return null|\Zend_Oauth_Token_Access
     */
    public function getAccessToken()
    {
        /** @var \Zend_Oauth_Token_Access $accessToken */
        $accessToken = $this->tokenData->getAccessToken();

        if (!($accessToken instanceof \Zend_Oauth_Token_Access)) {
            return null;
        }

        return $accessToken;
    }

    /**
     * @return bool
     */
    public function isAccessTokenNeedRenewal()
    {
        return $this->tokenData->isAccessTokenNeedRenewal();
    }

    /**
     * @param $queryString
     *
     * @return \Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function query($queryString)
    {
        /** @var string $apiUrl */
        $apiUrl = $this->config->getValue(
            ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_URL_QUICKBOOKS_API
        );

        /** @var string $requestUri */
        $requestUri = str_replace(
            ':companyId',
            $this->config->getValue(
                ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID
            ),
            ModelQuickbooks::API_QUERY
        );

        /** @var \Zend_Oauth_Token_Access $token */
        $token = $this->getAccessToken();

        if (!$token) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Unknown access token'));
        }

        $queryString.= self::MAX_RESULTS_QUERY_LIMITATION_STRING;

        /** @var \Zend_Oauth_Client $client */
        $client = $token->getHttpClient($this->quickbooksConfig->getConfig())
            ->setUri(htmlentities($apiUrl . $requestUri))
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData($queryString, 'application/text');

        /** @var \Zend_Http_Response $response */
        $response = $client->request();

        $this->logger->debug('QUICKBOOKS REQUEST URL:' . $client->getUri());
        $this->logger->debug('QUICKBOOKS REQUEST:' . $client->getLastRequest());
        $this->logger->debug('QUICKBOOKS RESPONSE STATUS:' . $response->getStatus());
        $this->logger->debug('QUICKBOOKS RESPONSE BODY:' . $response->getBody());

        return $response;
    }

    /**
     * @param $encodedData
     * @param $uri
     *
     * @return \Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function post($encodedData, $uri)
    {
        /** @var string $apiUrl */
        $apiUrl = $this->config->getValue(
            ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_URL_QUICKBOOKS_API
        );

        /** @var string $requestUri */
        $requestUri = str_replace(
            ':companyId',
            $this->config->getValue(
                ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID
            ),
            $uri
        );

        /** @var array $uriComponents */
        $uriComponents = $this->prepareUri($requestUri);

        /** @var \Zend_Oauth_Token_Access $token */
        $token = $this->getAccessToken();

        if (!$token) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Unknown access token'));
        }

        /** @var \Zend_Oauth_Client $client */
        $client = $token->getHttpClient($this->quickbooksConfig->getConfig())
            ->setMethod(\Zend_Http_Client::POST)
            ->setHeaders('Accept', 'application/json')
            ->setRawData($encodedData, 'application/json')
            ->setUri($apiUrl . $uriComponents['uri']);

        if (isset($uriComponents['query'])) {
            $client->setParameterGet(
                $uriComponents['query']['key'],
                $uriComponents['query']['value']
            );
        }

        /** @var \Zend_Http_Response $response */
        $response = $client->request();

        $this->logger->debug('QUICKBOOKS REQUEST URL: ' . $client->getUri());
        $this->logger->debug('QUICKBOOKS REQUEST:' . $client->getLastRequest());
        $this->logger->debug('QUICKBOOKS REQUEST BODY: ' . $encodedData);
        $this->logger->debug('QUICKBOOKS RESPONSE STATUS: ' . $response->getStatus());
        $this->logger->debug('QUICKBOOKS RESPONSE BODY: ' . $response->getBody());

        return $response;
    }

    /**
     * @param \Zend_Http_Response $response
     *
     * @return array
     */
    public function checkResponse(\Zend_Http_Response $response)
    {
        /** @var array $result */
        $result = [];

        if ($response) {
            /** @var string $responseBody */
            $responseBody = $response->getBody();

            /** @var string $responseBodyType */
            $responseBodyType = $this->isJson($responseBody) ? 'json' : 'xml';

            switch ($responseBodyType) {
                case 'xml':
                    $result = $this->processXMLResponse($response);
                    break;
                case 'json':
                    $result = $this->processJSONResponse($response);
                    break;
            }
        }

        return $result;
    }

    /**
     * @param $string
     *
     * @return bool
     */
    public function isJson($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * @param RequestInterface $request
     *
     * @return Quickbooks
     * @throws \Zend_Oauth_Exception
     */
    public function grant(RequestInterface $request)
    {
        /** @var \Zend_Oauth_Consumer $consumer */
        $consumer = $this->getConsumer();

        /** @var \Zend_Oauth_Token_Access $token */
        $token = $consumer->getAccessToken(
            $request->getParams(),
            $this->getRequestToken()
        );

        $this->setAccessToken($token);
        $this->setRequestToken();

        $companyId = $request->getParam('realmId');

        $this->setCompanyId($companyId);

        return $this;
    }

    /**
     * @return \Zend_Oauth_Token_Request
     */
    public function getRequestToken()
    {
        return $this->tokenData->getRequestToken();
    }

    /**
     * @param \Zend_Oauth_Token_Access $token
     *
     * @return \TNW\QuickbooksBasic\TokenData
     * @throws \Exception
     */
    public function setAccessToken(\Zend_Oauth_Token_Access $token)
    {
        return $this->tokenData->setAccessToken($token);
    }

    /**
     * Clear access token from database
     *
     * @return TokenData
     * @throws \Exception
     */
    public function clearAccessToken()
    {
        return $this->tokenData->clearAccessToken();
    }

    /**
     * @param \Zend_Oauth_Token_Request $token
     *
     * @return \TNW\QuickbooksBasic\TokenData
     */
    public function setRequestToken(\Zend_Oauth_Token_Request $token = null)
    {
        return $this->tokenData->setRequestToken($token);
    }

    /**
     * @return string
     */
    public function getRequestTokenUrl()
    {
        $consumer = $this->getConsumer();
        $this->setRequestToken($consumer->getRequestToken());

        return $consumer->getRedirectUrl();
    }

    /**
     * @return array
     */
    public function reconnect()
    {
        /** @var string $reconnectUrl */
        $reconnectUrl =
            'https://appcenter.intuit.com/api/v1/connection/reconnect';

        /** @var \Zend_Oauth_Token_Access $token */
        $token = $this->getAccessToken();

        if (!$token) {
            return [];
        }

        try {
            /** @var \Zend_Oauth_Client $client */
            $client = $token->getHttpClient(
                $this->quickbooksConfig->getConfig()
            );
            $client->setUri($reconnectUrl);
            $client->setMethod(\Zend_Http_Client::GET);
            $client->setHeaders('Accept', 'application/json');

            /** @var \Zend_Http_Response $response */
            $response = $client->request();
        } catch (\Exception $e) {
            return ['error' => 'true', 'message' => $e->getMessage()];
        }

        $this->logger->debug(
            'QUICKBOOKS REQUEST URL:' . $reconnectUrl
        );
        $this->logger->debug('QUICKBOOKS REQUEST:' . $client->getLastRequest());

        $this->logger->debug(
            'QUICKBOOKS RESPONSE STATUS:' . $response->getStatus()
        );
        $this->logger->debug(
            'QUICKBOOKS RESPONSE BODY:' . $response->getBody()
        );

        $responseBody = $this->checkResponse($response);

        if (isset($responseBody['ErrorCode']) &&
            $responseBody['ErrorCode'] == 0
        ) {
            $token->setToken($responseBody['OAuthToken'])->setTokenSecret(
                $responseBody['OAuthTokenSecret']
            );

            /**
             * save access token and renew date
             */
            $this->setAccessToken($token);

            /** @var array $result */
            $result = [
                'success' => 'true',
                'message' => 'Reconnection has been done.',
            ];
        } else {
            $errorCode = isset($responseBody['ErrorCode']) ?
                $responseBody['ErrorCode'] :
                null;

            $errorMessage = '';

            if ($errorCode) {
                switch ($errorCode) {
                    case 270:
                        $errorMessage = 'The OAuth access token has expired.';
                        break;
                    case 212:
                        $errorMessage = 'The request is made outside the 30-day window bounds.';
                        break;
                    case 22:
                        $errorMessage = 'The API requires authorization.';
                        break;
                    case 24:
                        $errorMessage = 'The app is not approved for the API.';
                        break;
                }
            }

            if (!$errorMessage) {
                $errorMessage = isset($responseBody['ErrorMessage'])
                    ? $responseBody['ErrorMessage']
                    : 'There was error during the request.';
            }

            /** @var array $result */
            $result = ['error' => 'true', 'message' => $errorMessage];
        }

        return $result;
    }

    /**
     * Disconnecting from QuickBooks
     *
     * @return array
     */
    public function disconnect()
    {
        /** @var string $disconnectUrl */
        $disconnectUrl =
            'https://appcenter.intuit.com/api/v1/connection/disconnect';

        /** @var \Zend_Oauth_Token_Access $token */
        $token = $this->getAccessToken();

        if (!$token) {
            return [];
        }

        try {
            /** @var \Zend_Oauth_Client $client */
            $client = $token->getHttpClient(
                $this->quickbooksConfig->getConfig()
            );
            $client->setUri($disconnectUrl);
            $client->setMethod(\Zend_Http_Client::GET);
            $client->setHeaders('Accept', 'application/json');

            /** @var \Zend_Http_Response $response */
            $response = $client->request();
        } catch (\Exception $e) {
            return ['error' => 'true', 'message' => $e->getMessage()];
        }

        $this->logger->debug(
            'QUICKBOOKS REQUEST URL:' . $disconnectUrl
        );
        $this->logger->debug('QUICKBOOKS REQUEST:' . $client->getLastRequest());

        $this->logger->debug(
            'QUICKBOOKS RESPONSE STATUS:' . $response->getStatus()
        );
        $this->logger->debug(
            'QUICKBOOKS RESPONSE BODY:' . $response->getBody()
        );

        $responseBody = $this->checkResponse($response);

        $this->clearAccessToken();

        if (isset($responseBody['ErrorCode'])
            && $responseBody['ErrorCode'] == 0
        ) {
            /** @var array $result */
            $result = [
                'success' => 'true',
                'message' => 'Disconnected Successfully!',
            ];
        } else {
            $errorCode = isset($responseBody['ErrorCode']) ? $responseBody['ErrorCode'] : null;
            $errorMessage = '';

            if ($errorCode) {
                switch ($errorCode) {
                    case 270:
                        $errorMessage = 'The OAuth access token has expired.';
                        break;
                    case 22:
                        $errorMessage = 'The API requires authorization.';
                        break;
                    case 24:
                        $errorMessage = 'The app is not approved for the API.';
                        break;
                }
            }

            if (!$errorMessage) {
                $errorMessage = isset($responseBody['ErrorMessage'])
                    ? $responseBody['ErrorMessage']
                    : 'There was error during the request.';
            }

            /** @var array $result */
            $result = ['error' => 'true', 'message' => $errorMessage];
        }

        return $result;
    }

    /**
     * @param string $companyId
     *
     * @return \TNW\QuickbooksBasic\TokenData
     */
    public function setCompanyId($companyId)
    {
        return $this->tokenData->setCompanyId($companyId);
    }

    /**
     * @param string $uri
     *
     * @return array
     */
    protected function prepareUri($uri)
    {
        /** @var array $data */
        $data = [];

        /** @var array $requestUriComponents */
        $requestUriComponents = explode('?', $uri);

        /** @var string $requestUri */
        $requestUri = $requestUriComponents[0];

        $data['uri'] = $requestUri;

        /** @var string $query */
        $query = isset($requestUriComponents[1]) ?
            $requestUriComponents[1] : '';

        /** @var array $queryComponents */
        $queryComponents = explode('=', $query);

        /** @var string $key */
        $key = isset($queryComponents[0]) ?
            $queryComponents[0] :
            '';

        /** @var string $value */
        $value = isset($queryComponents[1]) ?
            $queryComponents[1] :
            '';

        if ($key && $value) {
            $data['query']['key'] = $key;
            $data['query']['value'] = $value;
        }

        return $data;
    }

    /**
     * @return \Zend_Oauth_Consumer
     */
    protected function getConsumer()
    {
        return $this->tokenData->getConsumer(
            $this->quickbooksConfig->getConfig()
        );
    }

    /**
     * @param \Zend_Http_Response $response
     *
     * @return array
     * @throws LocalizedException
     */
    private function processXMLResponse($response)
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($response->getBody());
        if (!$xml) {
            $error = implode('; ', array_map(function (\LibXMLError $error) {
                return sprintf('Code: %s. Message: %s', $error->code, trim($error->message));
            }, libxml_get_errors()));

            libxml_clear_errors();
            throw new LocalizedException(__('XML Response Parse error: %1', $error));
        }

        /** @var array $result */
        $result = json_decode(json_encode((array)$xml), 1);
        $result = $this->parseResult($response, $result);

        return $result;
    }

    /**
     * @param \Zend_Http_Response $response
     *
     * @return array
     */
    private function processJSONResponse($response)
    {
        /** @var array $responseBody */
        $responseBody = $response->getBody();

        /** @var array $result */
        $result = $this->jsonDecoder->decode($responseBody);
        $result = $this->parseResult($response, $result);

        return $result;
    }

    /**
     * @param \Zend_Http_Response $response
     * @param $result
     * @return mixed
     */
    protected function parseResult($response, $result)
    {
        $resultLowCase= $this->arrayChangeKeyCaseRecursive($result);

        if ($response->getStatus() !== 200) {

            if (!empty($resultLowCase['fault']['error'])) {
                $result['Fault']['Error'] = '';

                /**
                 * correct array format
                 */
                if (!isset($resultLowCase['fault']['error'][0])) {
                    $errorArray = $resultLowCase['fault']['error'];
                    unset($resultLowCase['fault']['error']);

                    $resultLowCase['fault']['error'][0] = $errorArray;
                }

                foreach ($resultLowCase['fault']['error'] as $errorArray) {

                    $error = isset($errorArray['detail']) ?
                        $errorArray['detail'] :
                        $errorArray['message'];

                    $this->logger->error($error);
                    $result['Fault']['Error'] .= $error;
                }

            }
        }

        return $result;
    }

    /**
     * @param $arr
     * @return array
     */
    public function arrayChangeKeyCaseRecursive($arr)
    {
        return array_map(function ($item) {
            if (is_array($item))
                $item = $this->arrayChangeKeyCaseRecursive($item);
            return $item;
        }, array_change_key_case($arr));
    }
}
