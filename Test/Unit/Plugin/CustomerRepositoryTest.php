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
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;
use TNW\QuickbooksBasic\Model\Customer\CustomAttribute;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer as QuickbooksCustomerModel;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer;
use TNW\QuickbooksBasic\Plugin\CustomerRepository;

/**
 * @covers TNW\QuickbooksBasic\Plugin\CustomerRepository
 * Class CustomerRepositoryTest
 * @package TNW\QuickbooksBasic\Test\Unit\Plugin
 */
class CustomerRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var Customer $quickbooksCustomerMock */
    private $quickbooksCustomerMock;

    /** @var CustomerRepositoryInterface $customerRepositoryMock */
    private $customerRepositoryMock;

    /** @var LoggerInterface $loggerMock */
    private $loggerMock;

    /**  @var ScopeConfigInterface $coreConfigMock */
    private $coreConfigMock;

    /** @var  CustomAttribute $customAttributeMock */
    private $customAttributeMock;

    /** @var  ManagerInterface $messageManagerMock */
    private $messageManagerMock;

    /** @var AddressInterface $addressMock */
    private $addressMock;

    /** @var  AddressRepositoryInterface $addressRepositoryMock */
    private $addressRepositoryMock;

    /** @var  ObjectManager */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        /** @var CustomerRepositoryInterface $customerRepositoryMock */
        $this->customerRepositoryMock = $this->getMock(
            'Magento\Customer\Api\CustomerRepositoryInterface',
            [],
            [],
            '',
            false
        );

        /** @var LoggerInterface $loggerMock */
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

        /** @var CustomerInterface customerMock */
        $this->customerMock = $this->getMock(
            'Magento\Customer\Api\Data\CustomerInterface',
            [],
            [],
            '',
            false
        );

        /** @var State $stateMock */
        $this->state = $this->objectManager->getObject(State::class);

        $this->plugin = $this->objectManager->getObject(
            CustomerRepository::class,
            [
                'quickbooksCustomer' => $this->quickbooksCustomerMock,
                'logger' => $this->loggerMock,
                'coreConfig' => $this->coreConfigMock,
                'customAttribute' => $this->customAttributeMock,
                'messageManager' => $this->messageManagerMock,
                'state' => $this->state
            ]
        );

        parent::setUp();
    }

    /** @var  CustomerInterface $customerMock */
    protected $customerMock;

    /** @var  State $state */
    protected $state;

    /** @var  CustomerRepository $plugin */
    protected $plugin;

    /**
     * @covers TNW\QuickbooksBasic\Plugin\CustomerRepository::afterSave
     */
    public function testAfterSaveSynchronizationIsActive()
    {
        $this->beforeSave();

        $this->coreConfigMock->expects($this->exactly(2))
            ->method("getValue")
            ->withConsecutive(
                [
                    $this->identicalTo('quickbooks/general/active')
                ],
                [
                    $this->identicalTo('quickbooks_customer/customer/active')
                ]
            )->will($this->onConsecutiveCalls(
                $this->returnValue("1"),
                $this->returnValue("1")
            ));

        $this->quickbooksCustomerMock->expects($this->once())
            ->method('postCustomer')
            ->with($this->identicalTo($this->customerMock))
            ->willReturn([]);

        $this->state->setAreaCode(Area::AREA_FRONTEND);

        $result = $this->plugin->afterSave(
            $this->customerRepositoryMock,
            $this->customerMock
        );

        $this->assertInstanceOf(
            CustomerInterface::class,
            $result
        );
    }

    private function beforeSave()
    {
        $customerId = "testCustomerId";

        $this->customerMock->expects($this->exactly(2))
            ->method("getId")
            ->willReturn($customerId);

        $this->plugin->beforeSave(
            $this->customerRepositoryMock,
            $this->customerMock
        );
    }

    /**
     * @covers TNW\QuickbooksBasic\Plugin\CustomerRepository::afterSave
     */
    public function testAfterSaveSynchronizationException()
    {
        $this->beforeSave();

        $errorMessage = 'testErrorMessage';

        $this->coreConfigMock->expects($this->exactly(2))
            ->method("getValue")
            ->withConsecutive(
                [
                    $this->identicalTo('quickbooks/general/active')
                ],
                [
                    $this->identicalTo('quickbooks_customer/customer/active')
                ]
            )->will($this->onConsecutiveCalls(
                $this->returnValue("1"),
                $this->returnValue("1")
            ));

        $this->quickbooksCustomerMock->expects($this->once())
            ->method('postCustomer')
            ->with($this->identicalTo($this->customerMock))
            ->will($this->throwException(new \Exception($errorMessage)));

        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with($this->identicalTo($errorMessage))
            ->willReturn(null);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->identicalTo($errorMessage))
            ->willReturn(null);

        $result = $this->plugin->afterSave(
            $this->customerRepositoryMock,
            $this->customerMock
        );

        $this->assertInstanceOf(
            CustomerInterface::class,
            $result
        );
    }

    /**
     * @covers TNW\QuickbooksBasic\Plugin\CustomerRepository::afterSave
     */
    public function testAfterSaveSynchronizationIsNotActive()
    {
        $this->beforeSave();
        $syncStatus = 0;

        $this->coreConfigMock->expects($this->exactly(2))
            ->method("getValue")
            ->withConsecutive(
                [
                    $this->identicalTo('quickbooks/general/active')
                ],
                [
                    $this->identicalTo('quickbooks_customer/customer/active')
                ]
            )->will($this->onConsecutiveCalls(
                $this->returnValue("1"),
                $this->returnValue("0")
            ));

        $this->customerMock->expects($this->once())
            ->method('setCustomAttribute')
            ->with(
                QuickbooksCustomerModel::QUICKBOOKS_SYNC_STATUS,
                $syncStatus
            )
            ->willReturn(null);

        $this->customAttributeMock->expects($this->once())
            ->method('saveQuickbooksAttribute')
            ->with($this->identicalTo($this->customerMock));

        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $result = $this->plugin->afterSave(
            $this->customerRepositoryMock,
            $this->customerMock
        );

        $this->assertInstanceOf(
            CustomerInterface::class,
            $result
        );
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
        $this->state = null;
        parent::tearDown();
    }
}
