<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Service;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\Decoder;
use Magento\Framework\Json\Encoder;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TestFramework\App\State;
use Psr\Log\LoggerInterface;
use TNW\QuickbooksBasic\Model\Config as QuickbooksConfig;
use TNW\QuickbooksBasic\Service\Quickbooks as QuickbooksService;
use TNW\QuickbooksBasic\TokenData;
use TNW\QuickbooksBasic\Model\Quickbooks as ModelQuickbooks;

/**
 * @covers  TNW\QuickbooksBasic\Service\Quickbooks
 * Class QuickbooksTest
 * @package TNW\QuickbooksBasic\Test\Unit\Service
 */
class QuickbooksTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $config;

    /** @var \TNW\QuickbooksBasic\TokenData */
    protected $tokenData;

    /** @var \TNW\QuickbooksBasic\Model\Config */
    protected $quickbooksConfig;

    /** @var QuickbooksService */
    protected $quickbooksService;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messenger;

    /** @var  ObjectManager */
    private $objectManager;

    /** @var  State */
    private $state;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->config = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->tokenData = $this->getMockBuilder(TokenData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooksConfig = $this->getMockBuilder(QuickbooksConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->messenger = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonDecoder = $this->objectManager->getObject(Decoder::class);

        $this->jsonEncoder = $this->objectManager->getObject(Encoder::class);

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->state = $this->objectManager->getObject(State::class);
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $this->quickbooksService = $this->objectManager->getObject(
            QuickbooksService::class,
            [
                'config' => $this->config,
                'logger' => $this->logger,
                'tokenData' => $this->tokenData,
                'quickbooksConfig' => $this->quickbooksConfig,
                'messageManager' => $this->messenger,
                'decoder' => $this->jsonDecoder,
                'registry' => $this->registry,
                'state' => $this->state,
            ]
        );
    }

    /** @var \Magento\Framework\Json\Decoder */
    protected $jsonDecoder;

    /** @var \Magento\Framework\Json\Encoder */
    protected $jsonEncoder;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    public function testRead()
    {
        $apiRead = 'testApiRead';
        $apiUrl = 'testUrl';
        $companyId = 'companyTestId';
        $responseStatus = 'testStatus';
        $responseBody = 'testBody';
        $quickbooksId = 'testQuickbooksId';

        $requestTokenUrl = 'testRequestTokenUrl';
        $accessTokenUrl = 'testAccessTokenUrl';
        $userAuthorizationUrl = 'testUserAuthorizationUrl';
        $consumerKey = 'testConsumerKey';
        $consumerSecret = 'testConsumerSecret';
        $callbackUrl = 'testCallbackUrl';

        $config = [
            'requestTokenUrl' => $requestTokenUrl,
            'accessTokenUrl' => $accessTokenUrl,
            'userAuthorizationUrl' => $userAuthorizationUrl,
            'consumerKey' => $consumerKey,
            'consumerSecret' => $consumerSecret,
            'callbackUrl' => $callbackUrl,
        ];

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->setMethods(['getStatus', 'getBody'])
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('getStatus')
            ->willReturn($responseStatus);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($responseBody);

        $client = $this->getMockBuilder(\Zend_Oauth_Client::class)
            ->setMethods([
                'setUri',
                'setMethod',
                'setHeaders',
                'request',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('setUri')
            ->with($this->identicalTo($apiUrl . $apiRead))
            ->willReturn(null);
        $client->expects(($this->once()))
            ->method('setMethod')
            ->with($this->identicalTo("GET"))
            ->willReturn(null);
        $client->expects(($this->once()))
            ->method('setHeaders')
            ->with('Accept', 'application/json')
            ->willReturn(null);

        $client->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $token = $this->getMockBuilder(\Zend_Oauth_Token_Access::class)
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getHttpClient')
            ->with($this->identicalTo($config))
            ->willReturn($client);

        $this->tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($token);

        $this->quickbooksConfig->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->config->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['quickbooks/general/url_api_quickbooks'],
                [ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID]
            )
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue($apiUrl),
                    $this->returnValue($companyId)
                )
            );

        $this->logger->expects($this->exactly(3))
            ->method('debug')
            ->withConsecutive(
                ['QUICKBOOKS REQUEST URL:' . $apiUrl . $apiRead],
                ['QUICKBOOKS RESPONSE STATUS:' . $responseStatus],
                ['QUICKBOOKS RESPONSE BODY:' . $responseBody]
            )
            ->willReturn(null);

        $result = $this->quickbooksService->read($apiRead, $quickbooksId);

        $this->assertInstanceOf(\Zend_Http_Response::class, $result);
    }

    public function testReadNoAccessToken()
    {
        $apiRead = 'testApiRead';
        $apiUrl = 'testUrl';
        $companyId = 'testCompanyId';
        $quickbooksId = 'testQuickbooksId';

        $this->tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn(null);

        $this->config->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['quickbooks/general/url_api_quickbooks'],
                [ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID]
            )
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue($apiUrl),
                    $this->returnValue($companyId)
                )
            );

        $result = $this->quickbooksService->read($apiRead, $quickbooksId);

        $this->assertNull($result);
    }

    public function testReadClienThrowAcception()
    {
        $exception = 'testException';
        $apiRead = 'testApiRead';
        $apiUrl = 'testUrl';
        $companyId = 'companyTestId';
        $quickbooksId = 'testQuickbooksId';

        $requestTokenUrl = 'testRequestTokenUrl';
        $accessTokenUrl = 'testAccessTokenUrl';
        $userAuthorizationUrl = 'testUserAuthorizationUrl';
        $consumerKey = 'testConsumerKey';
        $consumerSecret = 'testConsumerSecret';
        $callbackUrl = 'testCallbackUrl';

        $config = [
            'requestTokenUrl' => $requestTokenUrl,
            'accessTokenUrl' => $accessTokenUrl,
            'userAuthorizationUrl' => $userAuthorizationUrl,
            'consumerKey' => $consumerKey,
            'consumerSecret' => $consumerSecret,
            'callbackUrl' => $callbackUrl,
        ];

        $client = $this->getMockBuilder(\Zend_Oauth_Client::class)
            ->setMethods([
                'setUri',
                'setMethod',
                'setHeaders',
                'request',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('setUri')
            ->with($this->identicalTo($apiUrl . $apiRead))
            ->willReturn(null);
        $client->expects(($this->once()))
            ->method('setMethod')
            ->with($this->identicalTo("GET"))
            ->willReturn(null);
        $client->expects(($this->once()))
            ->method('setHeaders')
            ->with('Accept', 'application/json')
            ->willReturn(null);

        $client->expects($this->once())
            ->method('request')
            ->will($this->throwException(new \Exception($exception)));

        $this->messenger->expects($this->once())
            ->method('addWarningMessage')
            ->with($this->identicalTo($exception))
            ->willReturn(null);

        $token = $this->getMockBuilder(\Zend_Oauth_Token_Access::class)
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getHttpClient')
            ->with($this->identicalTo($config))
            ->willReturn($client);

        $this->tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($token);

        $this->quickbooksConfig->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->config->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['quickbooks/general/url_api_quickbooks'],
                [ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID]
            )
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue($apiUrl),
                    $this->returnValue($companyId)
                )
            );

        $this->logger->expects($this->never())
            ->method('debug');

        $result = $this->quickbooksService->read($apiRead, $quickbooksId);

        $this->assertNull($result);
    }

    public function testQuery()
    {
        $queryString = 'testQueryString';
        $apiUrl = 'testApiUrl';
        $companyId = 'companyTestId';
        $responseStatus = 'testStatus';
        $responseBody = 'testBody';
        $lastRequest = 'testLastRequest';

        $requestTokenUrl = 'testRequestTokenUrl';
        $accessTokenUrl = 'testAccessTokenUrl';
        $userAuthorizationUrl = 'testUserAuthorizationUrl';
        $consumerKey = 'testConsumerKey';
        $consumerSecret = 'testConsumerSecret';
        $callbackUrl = 'testCallbackUrl';

        $config = [
            'requestTokenUrl' => $requestTokenUrl,
            'accessTokenUrl' => $accessTokenUrl,
            'userAuthorizationUrl' => $userAuthorizationUrl,
            'consumerKey' => $consumerKey,
            'consumerSecret' => $consumerSecret,
            'callbackUrl' => $callbackUrl,
        ];

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->setMethods(['getStatus', 'getBody'])
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('getStatus')
            ->willReturn($responseStatus);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($responseBody);

        $client = $this->getMockBuilder(\Zend_Oauth_Client::class)
            ->setMethods([
                'setUri',
                'setMethod',
                'setHeaders',
                'request',
                'setRawData',
                'getLastRequest',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('setUri')
            ->with($this->identicalTo('testApiUrlcompany/companyTestId/query'))
            ->willReturn(null);
        $client->expects(($this->once()))
            ->method('setMethod')
            ->with($this->identicalTo("POST"))
            ->willReturn(null);
        $client->expects($this->once())
            ->method('setRawData')
            ->with($queryString, 'application/text')
            ->willReturn(null);
        $client->expects($this->once())
            ->method('request')
            ->willReturn($response);
        $client->expects($this->once())
            ->method('getLastRequest')
            ->willReturn($lastRequest);

        $token = $this->getMockBuilder(\Zend_Oauth_Token_Access::class)
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getHttpClient')
            ->with($this->identicalTo($config))
            ->willReturn($client);

        $this->tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($token);

        $this->quickbooksConfig->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->config->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['quickbooks/general/url_api_quickbooks'],
                [ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID]
            )
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue($apiUrl),
                    $this->returnValue($companyId)
                )
            );

        $this->logger->expects($this->exactly(4))
            ->method('debug')
            ->withConsecutive(
                [
                    'QUICKBOOKS REQUEST URL:' .
                    'testApiUrlcompany/companyTestId/query',
                ],
                ['QUICKBOOKS REQUEST:' . $lastRequest],
                ['QUICKBOOKS RESPONSE STATUS:' . $responseStatus],
                ['QUICKBOOKS RESPONSE BODY:' . $responseBody]
            )
            ->willReturn(null);

        $result = $this->quickbooksService->query($queryString);

        $this->assertInstanceOf(\Zend_Http_Response::class, $result);
    }

    public function testQueryNoAccessToken()
    {
        $queryString = 'testQueryString';
        $apiUrl = 'testApiUrl';
        $companyId = 'testId';

        $this->tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn(null);

        $this->config->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['quickbooks/general/url_api_quickbooks'],
                [ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID]
            )
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue($apiUrl),
                    $this->returnValue($companyId)
                )
            );

        $result = $this->quickbooksService->query($queryString);

        $this->assertNull($result);
    }

    public function testQueryClientThrowException()
    {
        $exception = 'testException';
        $queryString = 'testQueryString';
        $apiUrl = 'testApiUrl';
        $companyId = 'companyTestId';

        $requestTokenUrl = 'testRequestTokenUrl';
        $accessTokenUrl = 'testAccessTokenUrl';
        $userAuthorizationUrl = 'testUserAuthorizationUrl';
        $consumerKey = 'testConsumerKey';
        $consumerSecret = 'testConsumerSecret';
        $callbackUrl = 'testCallbackUrl';

        $config = [
            'requestTokenUrl' => $requestTokenUrl,
            'accessTokenUrl' => $accessTokenUrl,
            'userAuthorizationUrl' => $userAuthorizationUrl,
            'consumerKey' => $consumerKey,
            'consumerSecret' => $consumerSecret,
            'callbackUrl' => $callbackUrl,
        ];

        $client = $this->getMockBuilder(\Zend_Oauth_Client::class)
            ->setMethods([
                'setUri',
                'setMethod',
                'setHeaders',
                'request',
                'setRawData',
                'getLastRequest',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('setUri')
            ->with($this->identicalTo('testApiUrlcompany/companyTestId/query'))
            ->willReturn(null);
        $client->expects(($this->once()))
            ->method('setMethod')
            ->with($this->identicalTo("POST"))
            ->willReturn(null);
        $client->expects($this->once())
            ->method('setRawData')
            ->with($queryString, 'application/text')
            ->willReturn(null);
        $client->expects($this->once())
            ->method('request')
            ->will($this->throwException(new \Exception($exception)));
        $client->expects($this->never())
            ->method('getLastRequest');

        $this->messenger->expects($this->once())
            ->method('addWarningMessage')
            ->with($this->identicalTo($exception))
            ->willReturn(null);

        $token = $this->getMockBuilder(\Zend_Oauth_Token_Access::class)
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getHttpClient')
            ->with($this->identicalTo($config))
            ->willReturn($client);

        $this->tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($token);

        $this->quickbooksConfig->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->config->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['quickbooks/general/url_api_quickbooks'],
                [ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID]
            )
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue($apiUrl),
                    $this->returnValue($companyId)
                )
            );

        $this->logger->expects($this->never())
            ->method('debug');

        $result = $this->quickbooksService->query($queryString);

        $this->assertNull($result);
    }

    public function testPost()
    {
        $encodedData = 'testEncodedData';
        $uri = 'testUri';
        $apiUrl = 'testUrl';
        $companyId = 'companyTestId';
        $responseStatus = 'testStatus';
        $responseBody = 'testBody';

        $requestTokenUrl = 'testRequestTokenUrl';
        $accessTokenUrl = 'testAccessTokenUrl';
        $userAuthorizationUrl = 'testUserAuthorizationUrl';
        $consumerKey = 'testConsumerKey';
        $consumerSecret = 'testConsumerSecret';
        $callbackUrl = 'testCallbackUrl';

        $config = [
            'requestTokenUrl' => $requestTokenUrl,
            'accessTokenUrl' => $accessTokenUrl,
            'userAuthorizationUrl' => $userAuthorizationUrl,
            'consumerKey' => $consumerKey,
            'consumerSecret' => $consumerSecret,
            'callbackUrl' => $callbackUrl,
        ];

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->setMethods(['getStatus', 'getBody'])
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('getStatus')
            ->willReturn($responseStatus);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($responseBody);

        $client = $this->getMockBuilder(\Zend_Oauth_Client::class)
            ->setMethods([
                'setUri',
                'setMethod',
                'setHeaders',
                'request',
                'setRawData',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('setUri')
            ->with($this->identicalTo($apiUrl . $uri))
            ->willReturn(null);
        $client->expects(($this->once()))
            ->method('setMethod')
            ->with($this->identicalTo("POST"))
            ->willReturn(null);
        $client->expects(($this->once()))
            ->method('setHeaders')
            ->with('Accept', 'application/json')
            ->willReturn(null);
        $client->expects($this->once())
            ->method('setRawData')
            ->with($encodedData, 'application/json')
            ->willReturn(null);
        $client->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $token = $this->getMockBuilder(\Zend_Oauth_Token_Access::class)
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getHttpClient')
            ->with($this->identicalTo($config))
            ->willReturn($client);

        $this->tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($token);

        $this->quickbooksConfig->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->config->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['quickbooks/general/url_api_quickbooks'],
                [ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID]
            )
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue($apiUrl),
                    $this->returnValue($companyId)
                )
            );

        $this->logger->expects($this->exactly(4))
            ->method('debug')
            ->withConsecutive(
                ['QUICKBOOKS REQUEST URL: ' . $apiUrl . $uri],
                ['QUICKBOOKS REQUEST BODY: ' . $encodedData],
                ['QUICKBOOKS RESPONSE STATUS: ' . $responseStatus],
                ['QUICKBOOKS RESPONSE BODY: ' . $responseBody]
            )
            ->willReturn(null);

        $result = $this->quickbooksService->post($encodedData, $uri);

        $this->assertInstanceOf(\Zend_Http_Response::class, $result);
    }

    public function testPostNoAccessToken()
    {
        $encodedData = 'testEncodedData';
        $uri = 'testUri';
        $apiUrl = 'testApiUrl';
        $companyId = 'testId';

        $this->tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn(null);

        $this->config->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['quickbooks/general/url_api_quickbooks'],
                [ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID]
            )
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue($apiUrl),
                    $this->returnValue($companyId)
                )
            );

        $result = $this->quickbooksService->post($encodedData, $uri);

        $this->assertNull($result);
    }

    public function testPostClientThrowException()
    {
        $exception = 'testException';
        $encodedData = 'testEncodedData';
        $uri = 'testUri';
        $apiUrl = 'testUrl';
        $companyId = 'companyTestId';

        $requestTokenUrl = 'testRequestTokenUrl';
        $accessTokenUrl = 'testAccessTokenUrl';
        $userAuthorizationUrl = 'testUserAuthorizationUrl';
        $consumerKey = 'testConsumerKey';
        $consumerSecret = 'testConsumerSecret';
        $callbackUrl = 'testCallbackUrl';

        $config = [
            'requestTokenUrl' => $requestTokenUrl,
            'accessTokenUrl' => $accessTokenUrl,
            'userAuthorizationUrl' => $userAuthorizationUrl,
            'consumerKey' => $consumerKey,
            'consumerSecret' => $consumerSecret,
            'callbackUrl' => $callbackUrl,
        ];

        $client = $this->getMockBuilder(\Zend_Oauth_Client::class)
            ->setMethods([
                'setUri',
                'setMethod',
                'setHeaders',
                'request',
                'setRawData',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('setUri')
            ->with($this->identicalTo($apiUrl . $uri))
            ->willReturn(null);
        $client->expects(($this->once()))
            ->method('setMethod')
            ->with($this->identicalTo("POST"))
            ->willReturn(null);
        $client->expects(($this->once()))
            ->method('setHeaders')
            ->with('Accept', 'application/json')
            ->willReturn(null);
        $client->expects($this->once())
            ->method('setRawData')
            ->with($encodedData, 'application/json')
            ->willReturn(null);
        $client->expects($this->once())
            ->method('request')
            ->will($this->throwException(new \Exception($exception)));

        $this->messenger->expects($this->once())
            ->method('addWarningMessage')
            ->with($this->identicalTo($exception))
            ->willReturn(null);

        $token = $this->getMockBuilder(\Zend_Oauth_Token_Access::class)
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getHttpClient')
            ->with($this->identicalTo($config))
            ->willReturn($client);

        $this->tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($token);

        $this->quickbooksConfig->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->config->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['quickbooks/general/url_api_quickbooks'],
                [ModelQuickbooks::XML_PATH_QUICKBOOKS_GENERAL_COMPANY_ID]
            )
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue($apiUrl),
                    $this->returnValue($companyId)
                )
            );

        $this->logger->expects($this->never())
            ->method('debug');

        $result = $this->quickbooksService->post($encodedData, $uri);

        $this->assertNull($result);
    }

    public function testCheckResponseJson()
    {
        $status = 200;
        $body = [
            'body' => 'testBody',
        ];
        $body = $this->jsonEncoder->encode($body);

        $data = ['body' => 'testBody'];

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        $response->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $result = $this->quickbooksService->checkResponse($response);

        $this->assertEquals($data, $result);
    }

    public function testCheckResponseXml()
    {
        $status = 200;
        $message = 'testBody';

        $body = '<?xml version="1.0" encoding="UTF-8"?>';
        $body .= '<document>';
        $body .= '<Body>' . $message . '</Body>';
        $body .= '</document>';
        ;

        $data = ['Body' => $message];

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        $response->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $result = $this->quickbooksService->checkResponse($response);

        $this->assertEquals($data, $result);
    }

    public function testCheckResponseIsNull()
    {
        $response = null;

        $result = $this->quickbooksService->checkResponse($response);

        $this->assertEmpty($result);
    }

    public function testCheckResponseXmlError()
    {
        $status = 500;
        $message = 'testMessage';

        $body = '<?xml version="1.0" encoding="UTF-8"?>';
        $body .= '<document>';
        $body .= '<Fault>';
        $body .= '<Error>';
        $body .= '<Message>' . $message . '</Message>';
        $body .= '</Error>';
        $body .= '</Fault>';
        $body .= '</document>';
        ;

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        $response->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $this->messenger->expects($this->once())
            ->method('addWarningMessage')
            ->with($this->identicalTo('QuickBooks: ' . $message));

        $result = $this->quickbooksService->checkResponse($response);

        $this->assertEmpty($result);
    }

    public function testCheckResponseJsonError()
    {
        $status = 500;
        $detail = 'testDetail';

        $body = [
            'Fault' => [
                'Error' => [
                    ['Detail' => $detail],
                ],
            ],
        ];

        $body = $this->jsonEncoder->encode($body);

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        $response->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $this->messenger->expects($this->once())
            ->method('addWarningMessage')
            ->with($this->identicalTo('QuickBooks: ' . $detail))
            ->willReturn(null);

        $result = $this->quickbooksService->checkResponse($response);

        $this->assertEmpty($result);
    }

    public function testGrant()
    {
        $params = 'testParams';
        $config = 'testConfig';
        $requestToken = new \Zend_Oauth_Token_Request();
        $accessToken = new \Zend_Oauth_Token_Access();

        $request = $this->getMockBuilder(RequestInterface::class)
            ->getMock();
        $request->expects($this->once())
            ->method('getParams')
            ->willReturn($params);

        $consumer = $this->getMockBuilder(\Zend_Oauth_Consumer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $consumer->expects($this->once())
            ->method('getAccessToken')
            ->with($params, $requestToken)
            ->willReturn($accessToken);

        $this->quickbooksConfig->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->tokenData->expects($this->once())
            ->method('getRequestToken')
            ->willReturn($requestToken);
        $this->tokenData->expects($this->once())
            ->method('getConsumer')
            ->with($this->identicalTo($config))
            ->willReturn($consumer);
        $this->tokenData->expects($this->once())
            ->method('setAccessToken')
            ->with($this->identicalTo($accessToken))
            ->willReturn($this->quickbooksService);
        $this->tokenData->expects($this->once())
            ->method('setRequestToken')
            ->with($this->identicalTo(null))
            ->willReturn($this->quickbooksService);

        /** @var RequestInterface $request */
        $result = $this->quickbooksService->grant($request);

        $this->assertInstanceOf(QuickbooksService::class, $result);
    }

    public function testGetAccessToken()
    {
        $token = new \Zend_Oauth_Token_Access();

        $this->tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($token);

        $result = $this->quickbooksService->getAccessToken();

        $this->assertEquals($token, $result);
    }

    public function testGetAccessTokenNoToken()
    {
        $token = null;

        $this->tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($token);

        $result = $this->quickbooksService->getAccessToken();

        $this->assertEquals($token, $result);
    }

    public function testSetAccessToken()
    {
        $token = new \Zend_Oauth_Token_Access();

        $this->tokenData->expects($this->once())
            ->method('setAccessToken')
            ->with($token)
            ->willReturn($this->tokenData);

        $result = $this->quickbooksService->setAccessToken($token);

        $this->assertInstanceOf(TokenData::class, $result);
    }

    public function testGetRequestTokenUrl()
    {
        $redirectUrl = 'testRedirectUrl';
        $config = 'testConfig';
        $requestToken = new \Zend_Oauth_Token_Request();

        $this->quickbooksConfig->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $consumer = $this->getMockBuilder(\Zend_Oauth_Consumer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $consumer->expects($this->once())
            ->method('getRequestToken')
            ->willReturn($requestToken);
        $consumer->expects($this->once())
            ->method('getRedirectUrl')
            ->willReturn($redirectUrl);

        $this->tokenData->expects($this->once())
            ->method('getConsumer')
            ->with($this->identicalTo($config))
            ->willReturn($consumer);
        $this->tokenData->expects($this->once())
            ->method('setRequestToken')
            ->with($this->identicalTo($requestToken))
            ->willReturn($this->quickbooksService);

        $result = $this->quickbooksService->getRequestTokenUrl();

        $this->assertEquals($redirectUrl, $result);
    }
}
