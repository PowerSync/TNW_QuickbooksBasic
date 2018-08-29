<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Plugin;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Message\ManagerInterface;
use TNW\QuickbooksBasic\Model\Customer\CustomAttribute;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer as QuickbooksCustomerModel;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer;
use TNW\QuickbooksBasic\Plugin\AddressRepository;

/**
 * @covers  TNW\QuickbooksBasic\Plugin\AddressRepository
 * Class AddressRepositoryTest
 * @package TNW\QuickbooksBasic\Test\Unit\Plugin
 */
class AddressRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var Customer $quickbooksCustomerMock */
    protected $quickbooksCustomerMock;

    /** @var CustomerRepositoryInterface $customerRepositoryMock */
    protected $customerRepositoryMock;

    /** @var \Psr\Log\LoggerInterface $loggerMock */
    protected $loggerMock;

    /**  @var ScopeConfigInterface $coreConfigMock */
    protected $coreConfigMock;

    /** @var  CustomAttribute $customAttributeMock */
    protected $customAttributeMock;

    /** @var  ManagerInterface $messageManagerMock */
    protected $messageManagerMock;

    /** @var AddressInterface $addressMock */
    protected $addressMock;

    /** @var  AddressRepositoryInterface $addressRepositoryMock */
    protected $addressRepositoryMock;

    /** @var  CustomerInterface $customerMock */
    protected $customerMock;

    /** @var  AddressRepository $plugin */
    protected $plugin;

    /**
     * @covers TNW\QuickbooksBasic\Plugin\AddressRepository::afterSave
     */
    public function testAfterSaveSynchronizationIsActive()
    {
        $customerId = 'testCustomerId';

        $this->coreConfigMock->expects($this->exactly(2))
            ->method("getValue")
            ->withConsecutive(
                [
                    $this->identicalTo('quickbooks/general/active'),
                ],
                [
                    $this->identicalTo('quickbooks_customer/customer/active'),
                ]
            )->will($this->onConsecutiveCalls(
                $this->returnValue("1"),
                $this->returnValue("1")
            ));

        $this->addressMock->expects($this->once())
            ->method("getCustomerId")
            ->willReturn($customerId);

        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($this->identicalTo($customerId))
            ->willReturn($this->customerMock);

        $this->quickbooksCustomerMock->expects($this->once())
            ->method('postCustomer')
            ->with($this->identicalTo($this->customerMock))
            ->willReturn([]);

        $this->messageManagerMock->expects($this->never())
            ->method('getMessages')
            ->with($this->identicalTo(true))
            ->willReturn(null);

        $result = $this->plugin->afterSave(
            $this->addressRepositoryMock,
            $this->addressMock
        );

        $this->assertInstanceOf(
            AddressInterface::class,
            $result
        );
    }

    /**
     * @covers TNW\QuickbooksBasic\Plugin\AddressRepository::afterSave
     */
    public function testAfterSaveSynchronizationException()
    {

        $customerId = 'testCustomerId';
        $errorMessage = 'testErrorMessage';

        $this->coreConfigMock->expects($this->exactly(2))
            ->method("getValue")
            ->withConsecutive(
                [
                    $this->identicalTo('quickbooks/general/active'),
                ],
                [
                    $this->identicalTo('quickbooks_customer/customer/active'),
                ]
            )->will($this->onConsecutiveCalls(
                $this->returnValue("1"),
                $this->returnValue("1")
            ));

        $this->addressMock->expects($this->once())
            ->method("getCustomerId")
            ->willReturn($customerId);

        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($this->identicalTo($customerId))
            ->willReturn($this->customerMock);

        $this->quickbooksCustomerMock->expects($this->once())
            ->method('postCustomer')
            ->with($this->identicalTo($this->customerMock))
            ->will($this->throwException(new \Exception($errorMessage)));

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with($this->identicalTo($errorMessage))
            ->willReturn(null);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->identicalTo($errorMessage))
            ->willReturn(null);

        $result = $this->plugin->afterSave(
            $this->addressRepositoryMock,
            $this->addressMock
        );

        $this->assertInstanceOf(
            AddressInterface::class,
            $result
        );
    }

    /**
     * @covers TNW\QuickbooksBasic\Plugin\AddressRepository::afterSave
     */
    public function testAfterSaveSynchronizationIsNotActive()
    {
        $customerId = 'testCustomerId';
        $syncStatus = 0;

        $this->coreConfigMock->expects($this->exactly(2))
            ->method("getValue")
            ->withConsecutive(
                [
                    $this->identicalTo('quickbooks/general/active'),
                ],
                [
                    $this->identicalTo('quickbooks_customer/customer/active'),
                ]
            )->will($this->onConsecutiveCalls(
                $this->returnValue("1"),
                $this->returnValue("0")
            ));

        $this->addressMock->expects($this->once())
            ->method("getCustomerId")
            ->willReturn($customerId);

        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($this->identicalTo($customerId))
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->once())
            ->method('setCustomAttribute')
            ->with(
                $this->identicalTo(
                    QuickbooksCustomerModel::QUICKBOOKS_SYNC_STATUS
                ),
                $this->identicalTo($syncStatus)
            )
            ->willReturn(null);

        $this->customAttributeMock->expects($this->once())
            ->method('saveQuickbooksAttribute')
            ->with($this->identicalTo($this->customerMock));

        $result = $this->plugin->afterSave(
            $this->addressRepositoryMock,
            $this->addressMock
        );

        $this->assertInstanceOf(
            AddressInterface::class,
            $result
        );
    }

    protected function setUp()
    {
        /** @var CustomerRepositoryInterface $customerRepositoryMock */
        $this->customerRepositoryMock = $this->getMock(
            'Magento\Customer\Api\CustomerRepositoryInterface',
            [],
            [],
            '',
            false
        );

        /** @var \Psr\Log\LoggerInterface $loggerMock */
        $this->loggerMock = $this->getMock(
            'Psr\Log\LoggerInterface',
            [],
            [],
            '',
            false
        );

        /**  @var ScopeConfigInterface $coreConfigMock */
        $this->coreConfigMock = $this->getMock(
            'Magento\Framework\App\Config\ScopeConfigInterface',
            [],
            [],
            '',
            false
        );

        /** @var  CustomAttribute $customAttributeMock */
        $this->customAttributeMock = $this->getMock(
            'TNW\QuickbooksBasic\Model\Customer\CustomAttribute',
            [],
            [],
            '',
            false
        );

        /** @var  ManagerInterface $messageManagerMock */
        $this->messageManagerMock = $this->getMock(
            'Magento\Framework\Message\ManagerInterface',
            [],
            [],
            '',
            false
        );

        /** @var Customer $quickbooksCustomerMock */
        $this->quickbooksCustomerMock = $this->getMock(
            'TNW\QuickbooksBasic\Model\Quickbooks\Customer',
            [],
            [],
            '',
            false
        );

        /** @var AddressInterface $addressMock */
        $this->addressMock = $this->getMock(
            'Magento\Customer\Api\Data\AddressInterface',
            [],
            [],
            '',
            false
        );

        /** @var AddressRepositoryInterface $addressRepositoryMock */
        $this->addressRepositoryMock = $this->getMock(
            'Magento\Customer\Api\AddressRepositoryInterface',
            [],
            [],
            '',
            false
        );

        /** @var CustomerInterface $customerMock */
        $this->customerMock = $this->getMock(
            'Magento\Customer\Api\Data\CustomerInterface',
            [],
            [],
            '',
            false
        );

        $this->plugin = new AddressRepository(
            $this->quickbooksCustomerMock,
            $this->customerRepositoryMock,
            $this->loggerMock,
            $this->coreConfigMock,
            $this->customAttributeMock,
            $this->messageManagerMock
        );

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->quickbooksCustomerMock = null;
        $this->customerRepositoryMock = null;
        $this->loggerMock = null;
        $this->coreConfigMock = null;
        $this->customAttributeMock = null;
        $this->messageManagerMock = null;
        $this->addressMock = null;
        $this->addressRepositoryMock = null;
        
        parent::tearDown();
    }
}
