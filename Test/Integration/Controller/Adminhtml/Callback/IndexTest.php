<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Integration\Controller\Adminhtml\Callback;

use Magento\TestFramework\TestCase\AbstractBackendController;
use TNW\QuickbooksBasic\Model\Quickbooks as QuickbooksModel;
use TNW\QuickbooksBasic\TokenData;
use Magento\Config\Model\Config\Factory;
use Magento\Config\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TestFramework\Response;
use Magento\TestFramework\ObjectManager;
use Magento\Framework\Message\Manager as MessageManager;

/**
 * Class IndexTest
 *
 * @package TNW\QuickbooksBasic\Test\Integration\Controller\Adminhtml\Callback
 */
class IndexTest extends AbstractBackendController
{
    const TEST_REQUEST_TOKEN = 'testRequestToken';
    const TEST_EXCEPTION = 'testException';
    const TEST_ACCESS_TOKEN = 'testAccessToken';

    /** @var  ObjectManager */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();

        $this->uri = 'backend/quickbooks/callback/index';
        $this->resource = 'TNW_QuickbooksBasic::config';

        $this->placeRequestToken();

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->removeRequestToken();

        parent::tearDown();
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testExecute()
    {
        /** @var \Zend_Oauth_Consumer $consumer */
        $consumer = $this->getConsumer();

        $this->replaceTokenData($consumer);

        $this->getRequest()->setParams(['param' => 'testParam']);

        $this->dispatch($this->uri);

        /** @var Response $response */
        $response = $this->getResponse();

        $this->assertEquals(302, $response->getHttpResponseCode());
        $this->assertRedirect(
            $this->stringContains(
                'admin/system_config/edit/section/quickbooks/'
            )
        );

        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->create(ScopeConfigInterface::class);

        $accessToken = $config->getValue(
            QuickbooksModel::XML_PATH_QUICKBOOKS_DATA_TOKEN_ACCESS
        );

        /** @var string $date */
        $date = $config->getValue(
            QuickbooksModel::XML_PATH_QUICKBOOKS_DATE_LAST_TIME_GET_DATA_TOKEN_ACCESS
        );

        $this->assertSame(\serialize($this->getAccessToken()), $accessToken);
        $this->assertSame(date('Y-m-d'), $date);
    }

    public function testExecuteException()
    {
        /** @var \Zend_Oauth_Consumer $consumer */
        $consumer = $this->getConsumer(true);

        $this->replaceTokenData($consumer);

        $this->getRequest()->setParams(['param' => 'testParam']);

        $this->dispatch($this->uri);

        /** @var Response $response */
        $response = $this->getResponse();

        /** @var MessageManager $messageManager */
        $messageManager = $this->objectManager->get(MessageManager::class);

        /** @var string $warning */
        $warning = $messageManager->getMessages()
            ->getLastAddedMessage()
            ->getText();

        $this->assertSame(self::TEST_EXCEPTION, $warning);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertRedirect(
            $this->stringContains(
                'admin/system_config/edit/section/quickbooks/'
            )
        );
    }

    /**
     * @param \Zend_Oauth_Consumer $consumer
     */
    private function replaceTokenData($consumer)
    {
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->create(ScopeConfigInterface::class);

        /** @var Factory $configFactory */
        $configFactory = $this->getConfigFactory();

        $tokenData = $this->getMockBuilder(TokenData::class)
            ->setConstructorArgs([
                $config,
                $configFactory,
            ])
            ->setMethods(['getConsumer'])
            ->getMock();
        $tokenData->expects($this->once())
            ->method('getConsumer')
            ->willReturn($consumer);

        $this->objectManager->addSharedInstance($tokenData, TokenData::class);
    }

    private function placeRequestToken()
    {
        /** @var \Zend_Oauth_Token_Request $requestToken */
        $requestToken = $this->getRequestToken();

        /** @var string $serializedToken */
        $serializedToken = \serialize($requestToken);

        /** @var Factory $configFactory */
        $configFactory = $this->getConfigFactory();

        /** @var Config $coreConfig */
        $coreConfig = $configFactory->create();
        $coreConfig->setDataByPath(
            QuickbooksModel::XML_PATH_QUICKBOOKS_DATA_TOKEN_REQUEST,
            $serializedToken
        );

        $coreConfig->save();
    }

    private function removeRequestToken()
    {
        /** @var Factory $configFactory */
        $configFactory = $this->getConfigFactory();

        /** @var Config $coreConfig */
        $coreConfig = $configFactory->create();
        $coreConfig->setDataByPath(
            QuickbooksModel::XML_PATH_QUICKBOOKS_DATA_TOKEN_REQUEST,
            null
        );

        $coreConfig->save();
    }

    /**
     * @param bool $throwException
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getConsumer($throwException = false)
    {
        /** @var \Zend_Oauth_Token_Access $accessToken */
        $accessToken = $this->getAccessToken();

        /** @var \Zend_Oauth_Token_Request $requestToken */
        $requestToken = $this->getRequestToken();

        $consumer = $this->getMockBuilder(\Zend_Oauth_Consumer::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($throwException) {
            $consumer->expects($this->once())
                ->method('getAccessToken')
                ->with(['param' => 'testParam'], $requestToken)
                ->will($this->throwException(
                    new \Exception(self::TEST_EXCEPTION)
                ));
        } else {
            $consumer->expects($this->once())
                ->method('getAccessToken')
                ->with(['param' => 'testParam'], $requestToken)
                ->willReturn($accessToken);
        }

        return $consumer;
    }

    /**
     * @return \Zend_Oauth_Token_Request
     */
    private function getRequestToken()
    {
        /** @var \Zend_Oauth_Token_Request $requestToken */
        $requestToken = $this->objectManager->create(
            \Zend_Oauth_Token_Request::class
        );
        $requestToken->setToken(self::TEST_REQUEST_TOKEN);

        return $requestToken;
    }

    /**
     * @return \Zend_Oauth_Token_Access
     */
    private function getAccessToken()
    {
        /** @var \Zend_Oauth_Token_Access $accessToken */
        $accessToken = $this->objectManager->create(
            \Zend_Oauth_Token_Access::class
        );
        $accessToken->setToken(self::TEST_ACCESS_TOKEN);

        return $accessToken;
    }

    /**
     * @return Factory
     */
    private function getConfigFactory()
    {
        /** @var Factory $configFactory */
        $configFactory = $this->objectManager->create(Factory::class);

        return $configFactory;
    }
}
