<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit;

use Magento\Config\Model\Config;
use Magento\Config\Model\Config\Factory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use TNW\QuickbooksBasic\Model\Quickbooks;
use TNW\QuickbooksBasic\TokenData;

/**
 * @covers  TNW\QuickbooksBasic\TokenData
 * Class TokenDataTest
 * @package TNW\QuickbooksBasic\Test\Unit
 */
class TokenDataTest extends \PHPUnit_Framework_TestCase
{
    /** @var ScopeConfigInterface */
    protected $config;

    /** @var Factory */
    protected $configFactory;

    /** @var TokenData */
    protected $tokenData;

    /**
     * @covers TNW\QuickbooksBasic\TokenData::getAccessToken
     */
    public function testGetAccessToken()
    {
        $data = new \Zend_Oauth_Token_Access();
        $data = serialize($data);

        $this->tokenData->expects($this->once())
            ->method('isAccessTokenExpired')
            ->willReturn(false);

        $this->config->expects($this->once())
            ->method('getValue')
            ->with(
                Quickbooks::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS
            )
            ->willReturn(
                $data
            );

        $result = $this->tokenData->getAccessToken();

        $this->assertInstanceOf(\Zend_Oauth_Token_Access::class, $result);
    }

    /**
     * @covers TNW\QuickbooksBasic\TokenData::setAccessToken
     */
    public function testSetAccessToken()
    {
        $token = new \Zend_Oauth_Token_Access();
        $serializedToken = serialize($token);

        $config = $this->getMockBuilder(Config::class)
            ->setMethods(['setDataByPath', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->exactly(2))
            ->method('setDataByPath')
            ->withConsecutive(
                ['quickbooks/data/token_access', $serializedToken],
                ['quickbooks/data/token_access_last_date', date('Y-m-d')]
            )
            ->will($this->returnSelf());
        $config->expects($this->exactly(2))
            ->method('save')
            ->will($this->returnSelf());

        $this->configFactory->expects($this->once())
            ->method('create')
            ->willReturn($config);

        $result = $this->tokenData->setAccessToken($token);

        $this->assertInstanceOf(TokenData::class, $result);
    }

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMock();

        $this->configFactory = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenData = $this->getMockBuilder(TokenData::class)
            ->setMethods(['isAccessTokenExpired'])
            ->setConstructorArgs([$this->config, $this->configFactory])
            ->getMock();
    }
}
