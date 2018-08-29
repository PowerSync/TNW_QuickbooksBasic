<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Model\ResourceModel;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResourceConnection\ConfigInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactoryInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use TNW\QuickbooksBasic\Model\Customer\CustomAttribute;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer as QuickbooksCustomer;

/**
 * @covers  TNW\QuickbooksBasic\Model\ResourceModel\Customer
 * Class CustomerTest
 * @package TNW\QuickbooksBasic\Test\Unit\Model\ResourceModel
 */
class CustomerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  CustomAttribute */
    protected $customAttributeMock;

    /** @var CustomerInterface */
    protected $magentoCustomerMock;

    /** @var ConfigInterface */
    protected $resourceConfigMock;

    /** @var ConnectionFactoryInterface */
    protected $connectionFactoryMock;

    /** @var \Magento\Framework\App\DeploymentConfig */
    protected $deploymentConfigMock;

    /** @var array */
    protected $data;

    /** @var \TNW\QuickbooksBasic\Model\ResourceModel\Customer */
    protected $resourceModelCustomerMock;

    /**
     * @covers TNW\QuickbooksBasic\Model\ResourceModel\Customer::syncQuickbooksResponse
     */
    public function testSyncQuickbooksResponse()
    {

        $customerId = 'testCustomerId';
        $customerSyncToken = 'testCustomerSync';
        $billAddrId = 'testBillAddrId';
        $shipAddrId = 'testShipAddrId';

        $data = [
            'Customer' => [
                'Id' => $customerId,
                'SyncToken' => $customerSyncToken,
                'BillAddr' => [
                    'Id' => $billAddrId,
                ],
                'ShipAddr' => [
                    'Id' => $shipAddrId,
                ],
            ],
        ];

        $this->magentoCustomerMock->expects($this->once())
            ->method('getDefaultBilling')
            ->willReturn($billAddrId);
        $this->magentoCustomerMock->expects($this->once())
            ->method('getDefaultShipping')
            ->willReturn($shipAddrId);

        $this->magentoCustomerMock->expects($this->exactly(3))
            ->method('setCustomAttribute')
            ->withConsecutive(
                [
                    $this->identicalTo(QuickbooksCustomer::QUICKBOOKS_ID),
                    $this->identicalTo($data['Customer']['Id']),
                ],
                [
                    $this->identicalTo(QuickbooksCustomer::QUICKBOOKS_SYNC_TOKEN),
                    $this->identicalTo($data['Customer']['SyncToken']),
                ],
                [
                    $this->identicalTo(QuickbooksCustomer::QUICKBOOKS_SYNC_STATUS),
                    $this->identicalTo(1),
                ]
            )
            ->willReturnSelf();

        $this->customAttributeMock->expects($this->once())
            ->method('saveQuickbooksAttribute')
            ->with($this->magentoCustomerMock);

        $connection = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceModelCustomerMock->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($connection);

        $connection->expects($this->exactly(2))
            ->method('insertOnDuplicate')
            ->withConsecutive(
                [
                    $this->identicalTo(
                        QuickbooksCustomer::BILLING_ADDRESS_QUICKBOOKS_TABLE
                    ),
                    $this->identicalTo(
                        [
                            QuickbooksCustomer::ADDRESS_ENTITY_ID => $billAddrId,
                            QuickbooksCustomer::QUICKBOOKS_ID => $data['Customer']['BillAddr']['Id'],
                        ]
                    ),
                ],
                [
                    $this->identicalTo(
                        QuickbooksCustomer::SHIPPINGG_ADDRESS_QUICKBOOKS_TABLE
                    ),
                    $this->identicalTo(
                        [
                            QuickbooksCustomer::ADDRESS_ENTITY_ID => $shipAddrId,
                            QuickbooksCustomer::QUICKBOOKS_ID => $data['Customer']['ShipAddr']['Id'],
                        ]
                    ),
                ]
            );

        $this->resourceModelCustomerMock->syncQuickbooksResponse(
            $data,
            $this->magentoCustomerMock
        );
    }

    protected function setUp()
    {
        /** @var CustomAttribute $customAttributeMock */
        $this->customAttributeMock = $this->getMock(
            'TNW\QuickbooksBasic\Model\Customer\CustomAttribute',
            [],
            [],
            '',
            false
        );

        /** @var CustomerInterface $magentoCustomerMock */
        $this->magentoCustomerMock = $this->getMock(
            'Magento\Customer\Api\Data\CustomerInterface',
            [],
            [],
            '',
            false
        );

        /** @var ConfigInterface $resourceConfigMock */
        $this->resourceConfigMock = $this->getMock(
            'Magento\Framework\App\ResourceConnection\ConfigInterface',
            [],
            [],
            '',
            false
        );

        /** @var ConnectionFactoryInterface $connectionFactoryMock */
        $this->connectionFactoryMock = $this->getMock(
            'Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactoryInterface',
            [],
            [],
            '',
            false
        );

        /** @var DeploymentConfig $deploymentConfigMock */
        $this->deploymentConfigMock = $this->getMock(
            'Magento\Framework\App\DeploymentConfig',
            [],
            [],
            '',
            false
        );

        $this->data = [];

        $this->resourceModelCustomerMock = $this->getMock(
            'TNW\QuickbooksBasic\Model\ResourceModel\Customer',
            ['getConnection'],
            [
                $this->resourceConfigMock,
                $this->connectionFactoryMock,
                $this->deploymentConfigMock,
                $this->customAttributeMock,
                '',
            ]
        );

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->customAttributeMock = null;
        $this->magentoCustomerMock = null;
        $this->resourceConfigMock = null;
        $this->connectionFactoryMock = null;
        $this->deploymentConfigMock = null;
        $this->resourceModelCustomerMock = null;

        parent::tearDown();
    }
}
