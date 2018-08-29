<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use TNW\QuickbooksBasic\Controller\Adminhtml\Customer\SyncCustomer;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer;

/**
 * Class SyncCustomerTest
 *
 * @package TNW\QuickbooksBasic\Test\Unit\Controller\Adminhtml\Customer
 */
class SyncCustomerTest extends \PHPUnit_Framework_TestCase
{
    const HTTP_TEST_REFERRER_URL = 'http://testReferrerUrl';
    const TEST_CUSTOMER_ID = 1;

    /** @var SyncCustomer */
    private $controller;

    /** @var Context mock */
    private $context;

    /** @var Redirect mock */
    private $resultRedirectMock;

    /** @var ObjectManagerInterface mock */
    private $objectManagerMock;

    /** @var ManagerInterface mock */
    private $messageManagerMock;

    /** @var  ObjectManager */
    private $objectManager;

    /** @var  RedirectInterface mock */
    private $redirect;

    /**
     * Test Set Up
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        /** @var Redirect resultRedirectMock */
        $this->resultRedirectMock = $this->getMock(
            'Magento\Framework\Controller\Result\Redirect',
            [],
            [],
            '',
            false
        );
        /** @var ObjectManagerInterface objectManagerMock */
        $this->objectManagerMock = $this->getMock(
            'Magento\Framework\ObjectManagerInterface',
            [],
            [],
            '',
            false
        );
        /** @var ManagerInterface messageManagerMock */
        $this->messageManagerMock = $this->getMock(
            'Magento\Framework\Message\ManagerInterface',
            [],
            [],
            '',
            false
        );
        /** @var CustomerRepositoryInterface customerRepositoryMock */
        $this->customerRepositoryMock = $this->getMock(
            'Magento\Customer\Api\CustomerRepositoryInterface',
            [],
            [],
            '',
            false
        );
        /** @var RequestInterface request */
        $this->request = $this->getMock(
            'Magento\Framework\App\RequestInterface'
        );

        $resultRedirectFactoryMock = $this->getMock(
            'Magento\Backend\Model\View\Result\RedirectFactory',
            [],
            [],
            '',
            false
        );
        $resultRedirectFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->resultRedirectMock));

        $this->redirect = $this->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Context contextMock */
        $this->context = $this->objectManager->getObject(
            'Magento\Backend\App\Action\Context',
            [
                'messageManager' => $this->messageManagerMock,
                'objectManager' => $this->objectManagerMock,
                'redirect' => $this->redirect,
                'resultRedirectFactory' => $resultRedirectFactoryMock,
                'request' => $this->request,
            ]
        );

        $this->controller = $this->objectManager->getObject(
            'TNW\QuickbooksBasic\Controller\Adminhtml\Customer\SyncCustomer',
            [
                'context' => $this->context,
                'customerRepository' => $this->customerRepositoryMock,
            ]
        );
    }

    /** @var RequestInterface mock */
    protected $request;

    /** @var CustomerRepositoryInterface mock */
    protected $customerRepositoryMock;

    /**
     * Test for \TNW\QuickbooksBasic\Controller\Adminhtml\Customer\SyncCustomer::execute
     *
     * @param $testData
     * @param $expectedRedirectRoute
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $testData,
        $expectedRedirectRoute
    ) {
        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with($expectedRedirectRoute)
            ->willReturnSelf();
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('customer_id')
            ->will($this->returnValue($testData['customer_id']));

        /** @var Customer $quickbooksCustomerMock */
        $quickbooksCustomerMock = $this->getMock(
            'TNW\QuickbooksBasic\Model\Quickbooks\Customer',
            [],
            [],
            '',
            false
        );
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customerMock */
        $customerMock = $this->getMock(
            'Magento\Customer\Api\Data\CustomerInterface',
            [],
            [],
            '',
            false
        );

        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($testData['customer_id'])
            ->will($this->returnValue($customerMock));
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with('TNW\QuickbooksBasic\Model\Quickbooks\Customer')
            ->will($this->returnValue($quickbooksCustomerMock));

        $quickbooksCustomerMock->expects($this->once())
            ->method('postCustomer')
            ->with($customerMock)
            ->will(
                array_key_exists('exception', $testData)
                    ? $this->throwException(
                        new \Exception($testData['exception'])
                    )
                    : $this->returnValue($testData['result'])
            );
        if (array_key_exists('exception', $testData)) {
            $this->messageManagerMock
                ->expects($this->once())
                ->method('addError')
                ->with($this->equalTo($testData['exception']))
                ->willReturnSelf();
        }

        $this->redirect->expects($this->once())
            ->method('getRefererUrl')
            ->willReturn(self::HTTP_TEST_REFERRER_URL);

        $this->assertSame(
            $this->resultRedirectMock,
            $this->controller->execute()
        );
    }

    /**
     * Test tear down
     */
    protected function tearDown()
    {
        $this->controller = null;
        $this->resultRedirectMock = null;
        $this->context = null;
        $this->objectManagerMock = null;
        $this->messageManagerMock = null;
        $this->customerRepositoryMock = null;
        $this->request = null;
    }

    /**
     * DataProvider for testExecute
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'No exception, result true, response body is not empty' => [
                [
                    'customer_id' => self::TEST_CUSTOMER_ID,
                    'result' => true,
                ],
                self::HTTP_TEST_REFERRER_URL,
            ],
            'No exception, result false, response body is empty' => [
                [
                    'customer_id' => self::TEST_CUSTOMER_ID,
                    'result' => false,
                ],
                self::HTTP_TEST_REFERRER_URL,
            ],
            'Exception on customer sync' => [
                [
                    'customer_id' => self::TEST_CUSTOMER_ID,
                    'exception' => 'customer exception',
                ],
                self::HTTP_TEST_REFERRER_URL,
            ],
        ];
    }
}
