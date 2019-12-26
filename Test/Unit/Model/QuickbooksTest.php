<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Model;

use Magento\Config\Model\Config;
use Magento\Config\Model\Config\Factory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use TNW\QuickbooksBasic\Model\Config as QuickbooksConfig;
use TNW\QuickbooksBasic\Model\Quickbooks;
use TNW\QuickbooksBasic\Service\Quickbooks as QuickbooksService;

/**
 * @covers \TNW\QuickbooksBasic\Model\Quickbooks
 */
class QuickbooksTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\Config\Model\Config\Factory */
    protected $configFactory;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $config;

    /** @var \Magento\Framework\UrlInterface */
    protected $urlBuilder;

    /** @var \Magento\Framework\Json\EncoderInterface */
    protected $jsonEncoder;

    /** @var \Magento\Framework\Json\DecoderInterface */
    protected $jsonDecoder;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Magento\Config\Model\Config */
    protected $configModel;

    /** @var \TNW\QuickbooksBasic\Model\Config */
    protected $quickbooksConfig;

    /** @var \TNW\QuickbooksBasic\Service\Quickbooks */
    protected $quickbooksService;

    /** @var \TNW\QuickbooksBasic\Model\Quickbooks */
    protected $quickbooks;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    public function setUp()
    {

        $this->configFactory = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonEncoder = $this->getMockBuilder(EncoderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonDecoder = $this->getMockBuilder(DecoderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configModel = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooksConfig = $this->getMockBuilder(QuickbooksConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooksService = $this->getMockBuilder(
            QuickbooksService::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooks = new Quickbooks(
            $this->configFactory,
            $this->config,
            $this->urlBuilder,
            $this->jsonEncoder,
            $this->jsonDecoder,
            $this->logger,
            $this->quickbooksConfig,
            $this->quickbooksService,
            $this->messageManager,
            $this->registry
        );
    }

    public function testGetConfig()
    {

        $config = [
            'config' => 'testConfig'
        ];

        $this->quickbooksConfig->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $result = $this->quickbooks->getConfig();

        $this->assertEquals($config, $result);
    }

    public function testGetIsActive()
    {
        $flag = true;

        $this->quickbooksConfig->expects($this->once())
            ->method('getIsActive')
            ->willReturn($flag);

        $result = $this->quickbooks->getIsActive();

        $this->assertTrue($result);
    }

    public function testGetRequestTokenUrl()
    {
        $url = 'testUrl';

        $this->quickbooksService->expects($this->once())
            ->method('getRequestTokenUrl')
            ->willReturn($url);

        $result = $this->quickbooks->getRequestTokenUrl();

        $this->assertEquals($url, $result);
    }

    public function testGetAccessToken()
    {
        $tokenAccess = new \Zend_Oauth_Token_Access();

        $this->quickbooksService->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($tokenAccess);

        $result = $this->quickbooks->getAccessToken();

        $this->assertEquals($tokenAccess, $result);
    }

    public function testGetQuickbooksService()
    {
        $result = $this->quickbooks->getQuickbooksService();

        $this->assertInstanceOf(QuickbooksService::class, $result);
    }

    public function testSetAccessToken()
    {
        $tokenRequest = new \Zend_Oauth_Token_Access();

        $this->quickbooksService->expects($this->once())
            ->method('setAccessToken')
            ->with($this->identicalTo($tokenRequest))
            ->willReturn(null);

        $result = $this->quickbooks->setAccessToken($tokenRequest);

        $this->assertInstanceOf(Quickbooks::class, $result);
    }

    public function testGrant()
    {
        $request = $this->getMockBuilder(RequestInterface::class)
            ->getMock();

        $this->quickbooksService->expects($this->once())
            ->method('grant')
            ->with($this->identicalTo($request))
            ->willReturn(null);

        /** @var RequestInterface $request */
        $result = $this->quickbooks->grant($request);

        $this->assertInstanceOf(Quickbooks::class, $result);
    }

    public function testQuery()
    {
        $query = 'testQuery';
        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooksService->expects($this->once())
            ->method('query')
            ->with($this->identicalTo($query))
            ->willReturn($response);

        $result = $this->quickbooks->query($query);

        $this->assertEquals($response, $result);
    }
}
