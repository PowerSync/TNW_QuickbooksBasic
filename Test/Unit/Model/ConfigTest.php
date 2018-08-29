<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use TNW\QuickbooksBasic\Model\Config as QuickbooksConfig;
use TNW\QuickbooksBasic\Model\Quickbooks;

/**
 * @covers TNW\QuickbooksBasic\Model\Config
 * Class ConfigTest
 * @package TNW\QuickbooksBasic\Test\Unit\Model
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $config;

    /** @var \Magento\Framework\UrlInterface */
    protected $urlBuilder;

    /** @var \TNW\QuickbooksBasic\Model\Config */
    protected $quickbooksConfig;

    public function testGetConfig()
    {
        $consumer_key = 'qyprdBr7giY6Bab1B0eorSQzf9aPOs';
        $consumer_secret = '9mqSkQLJY4mPsZRM8n4uL9FfTn226Was2Rl1x5Zc';
        $callbackUrl = 'testCallbackUrl';

        $data = [
            'requestTokenUrl' =>
                'https://oauth.intuit.com/oauth/v1/get_request_token',
            'accessTokenUrl' =>
                'https://oauth.intuit.com/oauth/v1/get_access_token',
            'userAuthorizationUrl' =>
                'https://appcenter.intuit.com/Connect/Begin',
            'consumerKey' => $consumer_key,
            'consumerSecret' => $consumer_secret,
            'callbackUrl' => $callbackUrl
        ];

        $this->config->expects($this->once())
            ->method('getValue')
            ->with($this->identicalTo(
                Quickbooks::XML_PATH_QUICKBOOKS_ENVIRONMENT
            ))->willReturn(1);

        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->willReturn($callbackUrl);

        $result = $this->quickbooksConfig->getConfig();

        $this->assertEquals($data, $result);
    }

    /*
     * @covers TNW\QuickbooksBasic\Model\Config::getConfig
     * Test helper recieved and return all necessary data
     */

    public function testGetIsActive()
    {

        $this->config->expects($this->once())
            ->method('isSetFlag')
            ->with($this->identicalTo('quickbooks/general/active'))
            ->willReturn(true);

        $result = $this->quickbooksConfig->getIsActive();

        $this->assertTrue($result);
    }

    /*
     * @covers TNW\QuickbooksBasic\Model\Config::getIsActive
     * Test helper recieved and return all necessary data
     */

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMock();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooksConfig = new QuickbooksConfig(
            $this->config,
            $this->urlBuilder
        );
    }
}
