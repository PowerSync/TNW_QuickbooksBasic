<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Integration\Controller\Adminhtml\Test;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractBackendController;
use TNW\QuickbooksBasic\TokenData;
use TNW\QuickbooksBasic\Model\Quickbooks\Company;
use TNW\QuickbooksBasic\Controller\Adminhtml\Test\Index;
use Magento\TestFramework\Response;

/**
 * Class IndexTest
 *
 * @package TNW\QuickbooksBasic\Test\Integration\Controller\Adminhtml\Test
 */
class IndexTest extends AbstractBackendController
{
    const TEST_MESSAGE = 'testMessage';
    /** @var  Index */
    protected $controller;

    /** @var  Company */
    protected $quickbooksCompany;

    /** @var  TokenData mock */
    protected $tokenData;

    /** @var  ObjectManager */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();

        $this->uri = 'backend/quickbooks/test/index';
        $this->resource = 'TNW_QuickbooksBasic::config';

        parent::setUp();
    }

    /**
     * @magentoDataFixture loadExecute
     * @dataProvider       executeDataProvider
     *
     * @param $expectedResponse
     */
    public function testExecute($expectedResponse)
    {
        $this->replaceTokenData(200);

        $this->dispatch($this->uri);

        /** @var Response $response */
        $response = $this->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedResponse['string'], $response->getBody());
    }

    /**
     * @magentoDataFixture loadExecute
     * @dataProvider       executeThrowExceptionDataProvider
     */
    public function testExecuteException($expectedResponse)
    {
        $this->replaceTokenData(403);

        $this->dispatch($this->uri);

        /** @var Response $response */
        $response = $this->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedResponse['string'], $response->getBody());
    }

    public function testAclHasAccess()
    {
        $this->replaceTokenData(200);

        parent::testAclHasAccess();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function replaceTokenData($status)
    {
        $body = 'testBody';

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($status !== 200) {
            $response->expects($this->once())
                ->method('getMessage')
                ->willReturn(self::TEST_MESSAGE);
        }

        $response->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn($status);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $client = $this->getMockBuilder(\Zend_Oauth_Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('setUri')
            ->with(
                'http://testQbUrlApi' .
                '/company/testCompanyId/companyinfo/testCompanyId'
            )
            ->willReturn(null);
        $client->expects($this->once())
            ->method('setMethod')
            ->with($this->identicalTo('GET'))
            ->willReturn(null);
        $client->expects($this->once())
            ->method('setHeaders')
            ->with('Accept', 'application/json')
            ->willReturn(null);
        $client->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $accessToken = $this->getMockBuilder(\Zend_Oauth_Token_Access::class)
            ->disableOriginalConstructor()
            ->getMock();
        $accessToken->expects($this->once())
            ->method('getHttpClient')
            ->willReturn($client);

        $tokenData = $this->getMockBuilder(TokenData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenData->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $this->objectManager->addSharedInstance($tokenData, TokenData::class);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'responseBody' => [
                    'string' => '{"success":"true","message":' .
                        '"Connection established."}',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function executeThrowExceptionDataProvider()
    {
        return [
            [
                'responseBody' => [
                    'string' =>
                        '{"error":"true","message":' .
                        '"Connection could not be established."}',
                ],
            ],
        ];
    }

    /** @codingStandardsIgnoreStart */
    public static function loadExecute()
    {
        include __DIR__ . '/../../../_files/qb_config.php';
    }
    /** @codingStandardsIgnoreEnd */
}
