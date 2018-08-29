<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Model\Quickbooks;

use Magento\Config\Model\Config\Factory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use TNW\QuickbooksBasic\Model\Config as QuickbooksConfig;
use TNW\QuickbooksBasic\Model\Quickbooks;
use TNW\QuickbooksBasic\Model\Quickbooks\Company;
use TNW\QuickbooksBasic\Service\Quickbooks as QuickbooksService;

/**
 * @covers  TNW\QuickbooksBasic\Model\Quickbooks\Company
 * Class CompanyTest
 * @package TNW\QuickbooksBasic\Test\Unit\Model\Quickbooks
 */
class CompanyTest extends \PHPUnit_Framework_TestCase
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

    /** @var \TNW\QuickbooksBasic\Model\Config */
    protected $quickbooksConfig;

    /** @var \TNW\QuickbooksBasic\Service\Quickbooks */
    protected $service;

    /** @var \TNW\QuickbooksBasic\Model\Quickbooks\Company */
    protected $company;

    /**
     * @covers TNW\QuickbooksBasic\Model\Quickbooks\Company::read
     */
    public function testRead()
    {
        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->service->expects($this->once())
            ->method('read')
            ->with('company/:companyId/companyinfo/:companyId', 0)
            ->willReturn($response);

        $result = $this->company->read();

        $this->assertEquals($response, $result);
    }

    protected function setUp()
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

        $this->quickbooksConfig = $this->getMockBuilder(QuickbooksConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = $this->getMockBuilder(QuickbooksService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_quickbooksConfig = $this->getMockBuilder(
            QuickbooksConfig::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->_service = $this->getMockBuilder(QuickbooksService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->company = new Company(
            $this->configFactory,
            $this->config,
            $this->urlBuilder,
            $this->jsonEncoder,
            $this->jsonDecoder,
            $this->logger,
            $this->quickbooksConfig,
            $this->service
        );
    }
}
