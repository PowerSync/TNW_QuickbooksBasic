<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Model\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use TNW\QuickbooksBasic\Model\Quickbooks;
use TNW\QuickbooksBasic\Model\Source\AbstractQuickbooksSource;
use TNW\QuickbooksBasic\Service\Quickbooks as QuickbooksService;

/**
 * Class AbstractQuickbooksSourceTest
 *
 * @package TNW\QuickbooksBasic\Test\Unit\Model\Source
 */
class AbstractQuickbooksSourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var \TNW\QuickbooksBasic\Model\Source\AbstractQuickbooksSource */
    protected $source;

    /** @var \Magento\Framework\Config\CacheInterface */
    protected $cache;

    /** @var \TNW\QuickbooksBasic\Model\Quickbooks */
    protected $quickbooks;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $config;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /** @var  \TNW\QuickbooksBasic\Service\Quickbooks */
    protected $quickbooksService;

    public function testToOptionArray()
    {
        $cacheId = 'testCacheId';

        $id1 = 'testId1';
        $label1 = 'testLabel1';

        $id2 = 'testId2';
        $label2 = 'testLabel2';

        $sourceList = [
            ['Id' => $id1, 'Name' => $label1],
            ['Id' => $id2, 'Name' => $label2],
        ];
        $serializedSourceList = \Zend_Json::encode($sourceList);

        $data = [
            ['value' => $id1, 'label' => $label1],
            ['value' => $id2, 'label' => $label2],
        ];

        $this->source->expects($this->once())
            ->method('getCacheId')
            ->willReturn($cacheId);

        $this->cache->expects($this->once())
            ->method('load')
            ->with($this->identicalTo($cacheId))
            ->willReturn($serializedSourceList);

        $result = $this->source->toOptionArray();

        $this->assertEquals($data, $result);
    }

    public function testToOptionArrayNoSourceListInCache()
    {
        $cacheId = 'testCacheId';
        $queryString = 'testQueryString';
        $queryResponseKey = 'testQueryResponseKey';

        $accountId1 = 'testAccountId1';
        $account1 = 'testAccount1';

        $accountId2 = 'testAccountId2';
        $account2 = 'testAccunt2';

        $data = [
            ['value' => $accountId1, 'label' => $account1],
            ['value' => $accountId2, 'label' => $account2],
        ];

        $sourceArr = [
            ['Id' => $accountId1, 'Name' => $account1],
            ['Id' => $accountId2, 'Name' => $account2],
        ];

        $sourceList = [
            'QueryResponse' => [
                $queryResponseKey => $sourceArr,
            ],
        ];

        $serializedSourceArr = \Zend_Json::encode($sourceArr);

        $this->source->expects($this->once())
            ->method('getCacheId')
            ->willReturn($cacheId);
        $this->source->expects($this->once())
            ->method('getQueryString')
            ->willReturn($queryString);
        $this->source->expects($this->once())
            ->method('getQueryResponseKey')
            ->willReturn($queryResponseKey);

        $accessToken = $this->getMockBuilder(\Zend_Oauth_Token_Access::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache->expects($this->once())
            ->method('load')
            ->with($this->identicalTo($cacheId))
            ->willReturn(null);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($serializedSourceArr, $cacheId)
            ->willReturn(null);

        $this->quickbooks->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);
        $this->quickbooks->expects($this->once())
            ->method('query')
            ->with($this->identicalTo($queryString))
            ->willReturn($response);
        $this->quickbooks->expects($this->once())
            ->method('getQuickbooksService')
            ->willReturn($this->quickbooksService);
        $this->quickbooksService->expects($this->once())
            ->method('checkResponse')
            ->willReturn($sourceList);

        $result = $this->source->toOptionArray();

        $this->assertEquals($data, $result);
    }

    public function testToOptionArrayNoAccessToken()
    {
        $cacheId = 'testCacheId';

        $data = [];

        $this->source->expects($this->once())
            ->method('getCacheId')
            ->willReturn($cacheId);

        $this->cache->expects($this->once())
            ->method('load')
            ->with($this->identicalTo($cacheId))
            ->willReturn(null);

        $this->quickbooks->expects($this->once())
            ->method('getAccessToken')
            ->willReturn(null);

        $result = $this->source->toOptionArray();

        $this->assertEquals($data, $result);
    }

    public function testGetSelectedOption()
    {
        $websiteId = 'testWebsiteId';
        $configPath = 'testConfigPath';
        $cacheId = 'testCacheId';

        $this->source->expects($this->once())
            ->method('getconfigPath')
            ->willReturn($configPath);
        $this->source->expects($this->once())
            ->method('getCacheId')
            ->willReturn($cacheId);

        $id1 = 'testId1';
        $label1 = 'testLabel1';

        $id2 = 'testId2';
        $label2 = 'testLabel2';

        $optionId = $id1;

        $sourceList = [
            ['Id' => $id1, 'Name' => $label1],
            ['Id' => $id2, 'Name' => $label2],
        ];

        $serializedSourceList = \Zend_Json::encode($sourceList);

        $data = ['Id' => $id1, 'Name' => $label1];

        $this->cache->expects($this->once())
            ->method('load')
            ->with($this->identicalTo($cacheId))
            ->willReturn($serializedSourceList);

        $this->config->expects($this->once())
            ->method('getValue')
            ->with($configPath, 'website', $websiteId)
            ->willReturn($optionId);

        $result = $this->source->getSelectedOption($websiteId);

        $this->assertEquals($data, $result);
    }

    public function testGetSelectedOptionNoWebsiteId()
    {
        $configPath = 'testConfigPath';
        $cacheId = 'testCacheId';

        $this->source->expects($this->once())
            ->method('getconfigPath')
            ->willReturn($configPath);
        $this->source->expects($this->once())
            ->method('getCacheId')
            ->willReturn($cacheId);

        $id1 = 'testId1';
        $label1 = 'testLabel1';

        $id2 = 'testId2';
        $label2 = 'testLabel2';

        $optionId = $id2;

        $sourceList = [
            ['Id' => $id1, 'Name' => $label1],
            ['Id' => $id2, 'Name' => $label2],
        ];

        $serializedSourceList = \Zend_Json::encode($sourceList);

        $data = ['Id' => $id2, 'Name' => $label2];

        $this->cache->expects($this->once())
            ->method('load')
            ->with($this->identicalTo($cacheId))
            ->willReturn($serializedSourceList);

        $this->config->expects($this->once())
            ->method('getValue')
            ->with($this->identicalTo($configPath))
            ->willReturn($optionId);

        $result = $this->source->getSelectedOption();

        $this->assertEquals($data, $result);
    }

    protected function setUp()
    {
        $this->cache = $this->getMockBuilder(CacheInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooks = $this->getMockBuilder(Quickbooks::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->source = $this->getMock(
            AbstractQuickbooksSource::class,
            $methods = [
                'getCacheId',
                'getQueryString',
                'getQueryResponseKey',
                'getConfigPath',
            ],
            $arguments = [
                $this->cache,
                $this->quickbooks,
                $this->config,
                $this->registry,
                $this->messageManager,
            ]
        );

        $this->quickbooksService = $this->getMockBuilder(
            QuickbooksService::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }
}
