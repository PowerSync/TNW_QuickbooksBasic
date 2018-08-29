<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Integration\Controller\Adminhtml\Connect;

use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\TestFramework\Response;
use TNW\QuickbooksBasic\Model\Quickbooks as QuickbooksModel;
use TNW\QuickbooksBasic\TokenData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Config\Model\Config\Factory;
use TNW\QuickbooksBasic\Model\Quickbooks;
use Magento\Config\Model\Config;

/**
 * Class IndexTest
 *
 * @package TNW\QuickbooksBasic\Test\Integration\Controller\Adminhtml\Connect
 */
class IndexTest extends AbstractBackendController
{
    const TEST_TOKEN = 'testToken';
    const TEST_REDIRECT_URL = 'testRedirectUrl';
    const TEST_EXCEPTION = 'testException';

    /** @var  TokenData mock */
    private $tokenData;

    /** @var  ObjectManager */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();

        $this->uri = 'backend/quickbooks/connect/index';
        $this->resource = 'TNW_QuickbooksBasic::config';

        parent::setUp();
    }

    /**
     * @magentoDbIsolation enabled
     * @dataProvider       executeDataProvider
     *
     * @param array $token
     * @param array $redirectUrl
     * @param array $responseBody
     * @param       $flag
     */
    public function testExecute($responseBody)
    {
        $consumer = $this->getConsumer();

        $this->replaceTokenData($consumer);

        $this->dispatch($this->uri);

        /** @var Response $response */
        $response = $this->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($responseBody['string'], $response->getBody());
    }

    /**
     * @dataProvider ExecuteThrowExceptionDataProvider
     *
     * @param $responseBody
     * @param $flag
     */
    public function testExecuteException($responseBody)
    {
        $consumer = $this->getConsumer(true);

        $this->replaceTokenData($consumer);

        $this->dispatch($this->uri);

        /** @var Response $response */
        $response = $this->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($responseBody['string'], $response->getBody());
    }

    /**
     * @param $consumer
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     * @internal param array $token
     * @internal param array $redirectUrl
     */
    private function replaceTokenData($consumer)
    {
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->create(
            ScopeConfigInterface::class
        );

        /** @var Factory $configFactory */
        $configFactory = $this->objectManager->create(Factory::class);

        $tokenData = $this->getMockBuilder(TokenData::class)
            ->setConstructorArgs([$config, $configFactory])
            ->setMethods(['getConsumer'])
            ->getMock();
        $tokenData->expects($this->once())
            ->method('getConsumer')
            ->willReturn($consumer);

        $this->objectManager->addSharedInstance($tokenData, TokenData::class);
    }

    /**
     * @param bool $willThrowException
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getConsumer($willThrowException = false)
    {
        $requestToken = $this->objectManager->create(
            \Zend_Oauth_Token_Request::class
        );
        $requestToken->setParams([
            'token' => self::TEST_TOKEN,
        ]);

        $consumer = $this->getMockBuilder(\Zend_Oauth_Consumer::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($willThrowException) {
            $consumer->expects($this->once())
                ->method('getRequestToken')
                ->will($this->throwException(
                    new \Exception(self::TEST_EXCEPTION)
                ));
        } else {
            $consumer->expects($this->once())
                ->method('getRequestToken')
                ->willReturn($requestToken);
            $consumer->expects($this->once())
                ->method('getRedirectUrl')
                ->willReturn(self::TEST_REDIRECT_URL);
        }

        return $consumer;
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'responseBody' => [
                    'string' =>
                        '{"success":"true","message":"Connecting...",' .
                        '"connect_url":"' . self::TEST_REDIRECT_URL . '"}',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function ExecuteThrowExceptionDataProvider()
    {
        return [
            [
                'responseBody' => [
                    'string' => '{"error":"true","message":' .
                        '"'. self::TEST_EXCEPTION . '"}',
                ],
            ],
        ];
    }
}
