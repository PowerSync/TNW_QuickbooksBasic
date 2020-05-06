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
    /**
     *
     */
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
     * @var \OAuth\Common\Http\Uri\UriFactory
     */
    protected $urlFactory;

    /**
     * @var \OAuth\Common\Http\Client\CurlClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var \OAuth\OAuth2\Token\StdOAuth2TokenFactory
     */
    protected $auth2TokenFactory;

    /**
     * Quickbooks constructor.
     * @param ScopeConfigInterface $config
     * @param LoggerInterface $logger
     * @param TokenData $tokenData
     * @param QuickbooksConfig $quickbooksConfig
     * @param ManagerInterface $messageManager
     * @param DecoderInterface $decoder
     * @param Registry $registry
     * @param \OAuth\Common\Http\Uri\UriFactory $urlFactory
     * @param State $state
     * @param \OAuth\Common\Http\Client\CurlClientFactory $httpClientFactory
     * @param \OAuth\OAuth2\Token\StdOAuth2TokenFactory $auth2TokenFactory
     */
    public function __construct(
        ScopeConfigInterface $config,
        LoggerInterface $logger,
        TokenData $tokenData,
        QuickbooksConfig $quickbooksConfig,
        ManagerInterface $messageManager,
        DecoderInterface $decoder,
        Registry $registry,
        \OAuth\Common\Http\Uri\UriFactory $urlFactory,
        State $state,
        \OAuth\Common\Http\Client\CurlClientFactory $httpClientFactory,
        \OAuth\OAuth2\Token\StdOAuth2TokenFactory $auth2TokenFactory
    ) {
        $this->auth2TokenFactory = $auth2TokenFactory;
        $this->httpClientFactory = $httpClientFactory;
        $this->urlFactory = $urlFactory;
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
     * @param int $quickbooksId
     * @return string
     * @throws LocalizedException
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

        $token = $this->getAccessToken();

        if (!$token) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Unknown access token'));
        }

        if ($token->getEndOfLife() !== \OAuth\Common\Token\TokenInterface::EOL_NEVER_EXPIRES
            && $token->getEndOfLife() !== \OAuth\Common\Token\TokenInterface::EOL_UNKNOWN
            && time() > $token->getEndOfLife()
        ) {
            $token = $this->refreshToken($token);
        }

        $requestHeaders = [
            'Authorization' => 'Bearer ' . $token->getAccessToken(),
            'Accept' => 'application/json'
        ];
        try {
            $response = $this->httpClientFactory->create()->retrieveResponse(
                $this->urlFactory->createFromAbsolute($apiUrl . $requestUri),
                null,
                $requestHeaders,
                \Zend_Http_Client::GET
            );
            $status = 200;
        } catch (\OAuth\Common\Http\Exception\TokenResponseException $e) {
            $status = $e->getCode();
            $response = $e->getMessage();
        }

        $this->logger->debug('QUICKBOOKS REQUEST URL:' . $apiUrl . $requestUri);
        $this->logger->debug('QUICKBOOKS REQUEST HEADERS:' . json_encode($requestHeaders));
        $this->logger->debug('QUICKBOOKS RESPONSE STATUS:' . $status);
        $this->logger->debug('QUICKBOOKS RESPONSE BODY:' . $response);

        return $response;
    }

    /**
     * @return mixed|null
     */
    public function getAccessToken()
    {
        return $this->tokenData->getAccessToken();
    }

    /**
     * @return bool
     */
    public function isAccessTokenNeedRenewal()
    {
        $token = $this->getAccessToken();
        return ($token->getEndOfLife() !== \OAuth\Common\Token\TokenInterface::EOL_NEVER_EXPIRES
            && $token->getEndOfLife() !== \OAuth\Common\Token\TokenInterface::EOL_UNKNOWN
            && time() > $token->getEndOfLife()
        );
    }

    /**
     * @param $queryString
     * @return string
     * @throws LocalizedException
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

        /** @var \OAuth\Common\Token\TokenInterface $token */
        $token = $this->getAccessToken();

        if (!$token) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Unknown access token'));
        }

        if ($token->getEndOfLife() !== \OAuth\Common\Token\TokenInterface::EOL_NEVER_EXPIRES
            && $token->getEndOfLife() !== \OAuth\Common\Token\TokenInterface::EOL_UNKNOWN
            && time() > $token->getEndOfLife()
        ) {
            $token = $this->refreshToken($token);
        }

        $queryString .= self::MAX_RESULTS_QUERY_LIMITATION_STRING;
        $headers = [
            'Authorization' => 'Bearer ' . $token->getAccessToken(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/text'
        ];

        try {
            $response = $this->httpClientFactory->create()->retrieveResponse(
                $this->urlFactory->createFromAbsolute($apiUrl . $requestUri),
                $queryString,
                $headers,
                \Zend_Http_Client::POST
            );
            $status = 200;
        } catch (\OAuth\Common\Http\Exception\TokenResponseException $e) {
            $status = $e->getCode();
            $response = $e->getMessage();
        }

        $this->logger->debug('QUICKBOOKS REQUEST URL:' . $apiUrl . $requestUri);
        $this->logger->debug('QUICKBOOKS REQUEST HEADERS:' . json_encode($headers));
        $this->logger->debug('QUICKBOOKS REQUEST BODY:' . $queryString);
        $this->logger->debug('QUICKBOOKS RESPONSE STATUS:' . $status);
        $this->logger->debug('QUICKBOOKS RESPONSE BODY:' . $response);

        return $response;
    }

    /**
     * @param $encodedData
     * @param $uri
     * @return string
     * @throws LocalizedException
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

        if ($token->getEndOfLife() !== \OAuth\Common\Token\TokenInterface::EOL_NEVER_EXPIRES
            && $token->getEndOfLife() !== \OAuth\Common\Token\TokenInterface::EOL_UNKNOWN
            && time() > $token->getEndOfLife()
        ) {
            $token = $this->refreshToken($token);
        }

        /** @var $httpClient \OAuth\Common\Http\Client\CurlClient */
        $httpClient = $this->httpClientFactory->create();
        $url = $this->urlFactory->createFromAbsolute($apiUrl . $uriComponents['uri']);

        if (isset($uriComponents['query'])) {
            $url->addToQuery($uriComponents['query']['key'], $uriComponents['query']['value']);
        }

        $headers = [
            'Authorization' => 'Bearer ' . $token->getAccessToken(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        try {
            $response = $httpClient->retrieveResponse(
                $url,
                $encodedData,
                $headers,
                \Zend_Http_Client::POST
            );
            $status = 200;
        } catch (\OAuth\Common\Http\Exception\TokenResponseException $e) {
            $status = $e->getCode();
            $response = $e->getMessage();
        }

        $this->logger->debug('QUICKBOOKS REQUEST URL:' . $url);
        $this->logger->debug('QUICKBOOKS REQUEST HEADERS:' . json_encode($headers));
        $this->logger->debug('QUICKBOOKS REQUEST BODY:' . $encodedData);
        $this->logger->debug('QUICKBOOKS RESPONSE STATUS:' . $status);
        $this->logger->debug('QUICKBOOKS RESPONSE BODY:' . $response);

        return $response;
    }

    /**
     * @param $response
     * @return array|mixed
     * @throws LocalizedException
     */
    public function checkResponse($response)
    {
        /** @var array $result */
        $result = [];

        if ($response) {
            /** @var string $responseBodyType */
            $responseBodyType = $this->isJson($response) ? 'json' : 'xml';

            switch ($responseBodyType) {
                case 'xml':
                    $result = $this->processXML($response);
                    break;
                case 'json':
                    $result = $this->processJSON($response);
                    break;
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
            if (is_array($item)) {
                $item = $this->arrayChangeKeyCaseRecursive($item);
            }
            return $item;
        }, array_change_key_case($arr));
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
     * @return $this
     * @throws Exception\InvalidStateException
     * @throws Exception\TokenResponseException
     */
    public function grant(RequestInterface $request)
    {
        if ($request->getParam('state') != $this->tokenData->getAuthTokenState()) {
            throw new Exception\InvalidStateException(__('Invalid State.'));
        }

        $bodyParams = array_merge(
            [
                'code' => $request->getParam('code'),
                'grant_type' => 'authorization_code',
            ],
            $this->quickbooksConfig->getConfig()
        );

        try {
            $responseBody = $this->httpClientFactory->create()->retrieveResponse(
                $this->urlFactory->createFromAbsolute(\TNW\QuickbooksBasic\Model\Config::ACCESS_TOKEN_URL),
                $bodyParams,
                []
            );
            $status = 200;
        } catch (\OAuth\Common\Http\Exception\TokenResponseException $e) {
            $status = $e->getCode();
            $responseBody = $e->getMessage();
        }

        $this->logger->debug('QUICKBOOKS REQUEST URL:' . \TNW\QuickbooksBasic\Model\Config::ACCESS_TOKEN_URL);
        $this->logger->debug('QUICKBOOKS REQUEST BODY:' . json_encode($bodyParams));
        $this->logger->debug('QUICKBOOKS RESPONSE STATUS:' . $status);
        $this->logger->debug('QUICKBOOKS RESPONSE BODY:' . $responseBody);

        $this->tokenData->setAuthTokenState('');
        $this->setAccessToken($this->parseAccessTokenResponse($responseBody));

        $companyId = $request->getParam('realmId');

        $this->setCompanyId($companyId);

        return $this;
    }

    /**
     * @param $token
     * @return TokenData
     */
    public function setAccessToken($token)
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
     * @return string
     */
    public function getRequestTokenUrl()
    {
        $parameters = $this->quickbooksConfig->getConfig();
        // Build the url
        $url = $this->urlFactory->createFromAbsolute(\TNW\QuickbooksBasic\Model\Config::AUTH_URL);
        foreach ($parameters as $key => $val) {
            $url->addToQuery($key, $val);
        }
        $state = md5(rand());
        $url->addToQuery('state', $state);
        $this->tokenData->setAuthTokenState($state);

        return $url->getAbsoluteUri();
    }

    /**
     * @return array
     */
    public function reconnect()
    {
        try {
            $result = $this->refreshToken();
        } catch (\Exception $e) {
            return ['error' => 'true', 'message' => $e->getMessage()];
        }
        return $result;
    }

    /**
     * @param null $token
     * @return mixed
     * @throws Exception\TokenResponseException
     */
    public function refreshToken($token = null)
    {
        if ($token === null) {
            $token = $this->getAccessToken();
        }
        $refreshToken = $token->getRefreshToken();

        if (empty($refreshToken)) {
            throw new \Exception(__('Missing Refresh Token'));
        }

        $bodyParams = array_merge(
            [
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ],
            $this->quickbooksConfig->getConfig()
        );

        try {
            $responseBody = $this->httpClientFactory->create()->retrieveResponse(
                $this->urlFactory->createFromAbsolute(\TNW\QuickbooksBasic\Model\Config::ACCESS_TOKEN_URL),
                $bodyParams,
                []
            );
            $status = 200;
        } catch (\OAuth\Common\Http\Exception\TokenResponseException $e) {
            $status = $e->getCode();
            $responseBody = $e->getMessage();
        }

        $this->logger->debug('QUICKBOOKS REQUEST URL:' . \TNW\QuickbooksBasic\Model\Config::ACCESS_TOKEN_URL);
        $this->logger->debug('QUICKBOOKS REQUEST BODY:' . json_encode($bodyParams));
        $this->logger->debug('QUICKBOOKS RESPONSE STATUS:' . $status);
        $this->logger->debug('QUICKBOOKS RESPONSE BODY:' . $responseBody);

        $accessToken = $this->parseAccessTokenResponse($responseBody);
        $this->setAccessToken($accessToken);
        return $accessToken;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function disconnect()
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return [];
        }
        $config = $this->quickbooksConfig->getConfig();
        $body = json_encode(['token' => $token->getAccessToken()]);
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($config['client_id'] . ":" . $config['client_secret']),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
        try {

            $response = $this->httpClientFactory->create()->retrieveResponse(
                $this->urlFactory->createFromAbsolute(\TNW\QuickbooksBasic\Model\Config::DISCONNECT_TOKEN_URL),
                $body,
                $headers,
                \Zend_Http_Client::POST
            );
            $result = [
                'success' => 'true',
                'message' => 'Disconnected Successfully!',
            ];
            $status = 200;
        } catch (\Exception $e) {
            return ['error' => 'true', 'message' => $e->getMessage()];
        }

        $this->logger->debug(
            'QUICKBOOKS REQUEST URL:' . \TNW\QuickbooksBasic\Model\Config::DISCONNECT_TOKEN_URL
        );
        $this->logger->debug('QUICKBOOKS REQUEST HEADERS:' . json_encode($headers));
        $this->logger->debug('QUICKBOOKS REQUEST BODY:' . $body);
        $this->logger->debug('QUICKBOOKS RESPONSE STATUS:' . $status);
        $this->logger->debug(
            'QUICKBOOKS RESPONSE BODY:' . $response
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

            if (!$errorMessage && $errorCode) {
                $errorMessage = isset($responseBody['ErrorMessage'])
                    ? $responseBody['ErrorMessage']
                    : 'There was error during the request.';
            }

            if ($errorMessage) {
                /** @var array $result */
                $result = ['error' => 'true', 'message' => $errorMessage];
            }
        }

        return $result;
    }

    /**
     * @param $companyId
     * @return TokenData
     * @throws \Exception
     */
    public function setCompanyId($companyId)
    {
        return $this->tokenData->setCompanyId($companyId);
    }

    /**
     * @param $uri
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
     * @return \OAuth\Common\Consumer\Credentials
     */
    protected function getConsumer()
    {
        return $this->tokenData->getConsumer(
            $this->quickbooksConfig->getConfig()
        );
    }


    /**
     * @param $responseBody
     * @return mixed
     * @throws Exception\TokenResponseException
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = $this->jsonDecoder->decode($responseBody);
        if (null === $data || !is_array($data)) {
            throw new Exception\TokenResponseException(__('Unable to parse response.'));
        } elseif (isset($data['error_description']) || isset($data['error'])) {
            throw new Exception\TokenResponseException(
                __(
                    'Error in retrieving token: "%1"',
                    isset($data['error_description']) ? $data['error_description'] : $data['error']
                )
            );
        }

        $token = $this->auth2TokenFactory->create();
        $token->setAccessToken($data['access_token']);
        $token->setLifeTime($data['expires_in']);

        if (isset($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            unset($data['refresh_token']);
        }

        unset($data['access_token']);
        unset($data['expires_in']);

        $token->setExtraParams($data);

        return $token;
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
     *
     * @return array
     * @throws LocalizedException
     */
    private function processXML($response)
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($response);
        if (!$xml) {
            $error = implode('; ', array_map(function (\LibXMLError $error) {
                return sprintf('Code: %s. Message: %s', $error->code, trim($error->message));
            }, libxml_get_errors()));

            libxml_clear_errors();
            throw new LocalizedException(__('XML Response Parse error: %1', $error));
        }

        /** @var array $result */
        $result = json_decode(json_encode((array)$xml), 1);
        return $result;
    }

    /**
     * @param $responseBody
     * @return mixed
     */
    private function processJSON($responseBody)
    {
        return $this->jsonDecoder->decode($responseBody);
    }

    /**
     * @param \Zend_Http_Response $response
     * @param $result
     * @return mixed
     */
    protected function parseResult($response, $result)
    {
        $resultLowCase = $this->arrayChangeKeyCaseRecursive($result);

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
}
