<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Model\Customer;

use Magento\Customer\Model\Backend\Customer as BackendCustomer;
use Magento\Customer\Model\ResourceModel\Customer as ResourceCustomer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer;

/**
 * Class CustomAttributeTest
 *
 * @package TNW\QuickbooksBasic\Test\Unit\Model\Customer
 */
class CustomAttributeTest extends \PHPUnit_Framework_TestCase
{

    /** @var \TNW\QuickbooksBasic\Model\Customer\CustomAttribute */
    protected $customAttribute;

    /** @var  BackendCustomer mock */
    protected $backendCustomerMock;

    /** @var  ResourceCustomer mock */
    protected $resourceCustomerMock;

    /** @var  \Magento\Customer\Api\Data\CustomerInterface mock */
    protected $customerMock;

    /** @var  \Magento\Framework\Api\CustomAttributesDataInterface mock */
    protected $customAttributeMock;

    /**
     * @param $attributeCodes
     * @param $attributeValues
     * @param $customerObjectValues
     *
     * @dataProvider getAllAttributeCodes
     */
    public function testSaveQuickbooksAttribute(
        $attributeCodes,
        $attributeValues,
        $customerObjectValues
    ) {
        $customerId = 1;

        $this->customerMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($customerId));

        $this->customerMock->expects($this->exactly(count($attributeCodes) * 2))
            ->method('getCustomAttribute')
            ->withConsecutive(
                [$this->identicalTo($attributeCodes[0])],
                [$this->identicalTo($attributeCodes[0])],
                [$this->identicalTo($attributeCodes[1])],
                [$this->identicalTo($attributeCodes[1])],
                [$this->identicalTo($attributeCodes[2])],
                [$this->identicalTo($attributeCodes[2])]
            )
            ->will($this->onConsecutiveCalls(
                $this->returnValue($this->customAttributeMock),
                $this->returnValue($this->customAttributeMock),
                $this->returnValue($this->customAttributeMock),
                $this->returnValue($this->customAttributeMock),
                $this->returnValue($this->customAttributeMock),
                $this->returnValue($this->customAttributeMock)
            ));

        $this->customAttributeMock->expects(
            $this->exactly(count($attributeCodes))
        )
            ->method('getValue')
            ->will($this->onConsecutiveCalls(
                $this->returnValue($attributeValues[0]),
                $this->returnValue($attributeValues[1]),
                $this->returnValue($attributeValues[2])
            ));

        $this->backendCustomerMock->expects(
            $this->exactly(count($attributeCodes))
        )
            ->method('setData')
            ->withConsecutive(
                [
                    $this->identicalTo($attributeCodes[0]),
                    $this->identicalTo($attributeValues[0]),
                ],
                [
                    $this->identicalTo($attributeCodes[1]),
                    $this->identicalTo($attributeValues[1]),
                ],
                [
                    $this->identicalTo($attributeCodes[2]),
                    $this->identicalTo($attributeValues[2]),
                ]
            )
            ->will($this->onConsecutiveCalls(
                $this->returnSelf(),
                $this->returnSelf(),
                $this->returnSelf()
            ));

        $this->backendCustomerMock->expects(
            $this->exactly(count($attributeCodes))
        )
            ->method('getOrigData')
            ->withConsecutive(
                [$this->identicalTo($attributeCodes[0])],
                [$this->identicalTo($attributeCodes[1])],
                [$this->identicalTo($attributeCodes[2])]
            )
            ->will($this->onConsecutiveCalls(
                $this->identicalTo($customerObjectValues[0]),
                $this->identicalTo($customerObjectValues[1]),
                $this->identicalTo($customerObjectValues[2])
            ));

        $this->backendCustomerMock->expects(
            $this->exactly(count($attributeCodes))
        )
            ->method('getData')
            ->withConsecutive(
                [$this->identicalTo($attributeCodes[0])],
                [$this->identicalTo($attributeCodes[1])],
                [$this->identicalTo($attributeCodes[2])]
            )
            ->will($this->onConsecutiveCalls(
                $this->identicalTo($attributeValues[0]),
                $this->identicalTo($attributeValues[1]),
                $this->identicalTo($attributeValues[2])
            ));

        $saveAttributes = [];

        /** @var int $numberOfAttributesCodes */
        $numberOfAttributesCodes = count($attributeCodes);

        for ($i = 0; $i < $numberOfAttributesCodes; $i++) {
            if ($customerObjectValues[$i] !== $attributeValues[$i]) {
                $saveAttributes[$i] = $attributeCodes[$i];
            }
        }

        $this->resourceCustomerMock->expects($this->exactly(
            count($saveAttributes)
        ))
            ->method('saveAttribute')
            ->willReturnSelf();

        $this->backendCustomerMock->expects($this->once())
            ->method('load')
            ->with($customerId)
            ->willReturnSelf();

        $this->backendCustomerMock->expects($this->once())
            ->method('reindex');

        $this->customAttribute->saveQuickbooksAttribute($this->customerMock);
    }

    /**
     * DataProvider for testSaveQuickbooksAttribute
     *
     * @return array
     */
    public function getAllAttributeCodes()
    {
        return [
            'data' => [
                'attribute codes' => [
                    Customer::QUICKBOOKS_ID,
                    Customer::QUICKBOOKS_SYNC_STATUS,
                    Customer::QUICKBOOKS_SYNC_TOKEN,
                ],
                'attributes values' => [
                    '1',
                    '2',
                    '3',
                ],
                'customer object values' => [
                    '1',
                    '4',
                    '4',
                ],
            ],
        ];
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $customAttribute = null;
        $backendCustomerMock = null;
        $resourceCustomerMock = null;
    }

    /**
     * Test setup
     */
    protected function setUp()
    {
        /** @var BackendCustomer $backendCustomerMock */
        $this->backendCustomerMock = $this->getMock(
            'Magento\Customer\Model\Backend\Customer',
            [],
            [],
            '',
            false
        );

        /** @var ResourceCustomer $resourceCustomerMock */
        $this->resourceCustomerMock = $this->getMock(
            'Magento\Customer\Model\ResourceModel\Customer',
            [],
            [],
            '',
            false
        );

        /** @var \Magento\Customer\Api\Data\CustomerInterface $customerMock */
        $this->customerMock = $this->getMock(
            'Magento\Customer\Api\Data\CustomerInterface',
            [],
            [],
            '',
            false
        );

        /** @var  \Magento\Framework\Api\CustomAttributesDataInterface mock */
        $this->customAttributeMock = $this->getMock(
            'Magento\Framework\Api\AttributeValue',
            ['getValue'],
            [],
            '',
            false
        );

        $objectManagerHelper = new ObjectManager($this);

        $this->customAttribute = $objectManagerHelper->getObject(
            'TNW\QuickbooksBasic\Model\Customer\CustomAttribute',
            [
                'resourceCustomer' => $this->resourceCustomerMock,
                'backendCustomer' => $this->backendCustomerMock,
            ]
        );
    }
}
