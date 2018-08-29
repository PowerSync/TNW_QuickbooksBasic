<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Unit\Model\Quickbooks;

use Magento\Config\Model\Config\Factory;
use Magento\Customer\Api\Data\AddressExtension;
use Magento\Customer\Api\Data\AddressExtensionFactory;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomer;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressFactory;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use TNW\QuickbooksBasic\Model\Config as QuickbooksConfig;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer as QuickbooksCustomer;
use TNW\QuickbooksBasic\Model\ResourceModel\Customer as QuickbooksCustomerResource;
use TNW\QuickbooksBasic\Service\Quickbooks as QuickbooksService;

/**
 * @covers TNW\QuickbooksBasic\Model\Quickbooks\Customer
 * Class CustomerTest
 * @package TNW\QuickbooksBasic\Test\Unit\Model\Quickbooks
 */
class CustomerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \TNW\QuickbooksBasic\Model\Quickbooks\Customer */
    protected $quickbooksCustomer;

    /** @var \Magento\Customer\Api\Data\CustomerInterface */
    protected $magentoCustomer;

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
    protected $quickbooksService;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /** @var \TNW\QuickbooksBasic\Model\ResourceModel\Customer */
    protected $customerResource;

    /** @var \Magento\Customer\Model\AddressFactory */
    protected $addressFactory;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Magento\Customer\Api\Data\AddressExtensionFactory */
    protected $addressExtensionFactory;

    /** @var \Magento\Customer\Api\Data\AddressExtension */
    protected $addressExtension;

    /** @var  \Magento\Customer\Api\Data\AddressInterface */
    protected $addressInterface;

    /** @var  \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    /** @var  \Magento\Framework\DB\Select */
    protected $select;

    /** @var  \Magento\Customer\Api\Data\RegionInterface */
    protected $region;

    /** @var  \Magento\Framework\Api\AttributeInterface */
    protected $attribute;

    /**
     * @covers TNW\QuickbooksBasic\Model\Quickbooks\Customer::postCustomer
     */
    public function testPostCustomerWithQuickbooksIdAndBillingCompany()
    {
        #region PrepareData

        #region initialData
        $prefix = 'testPrefix';
        $firstName = 'testFirstName';
        $middleName = 'testMiddleName';
        $lastName = 'testLastName';
        $suffix = 'suffix';
        $name = $prefix . ' ' . $firstName . ' ' .
            $middleName . ' ' . $lastName . ' ' . $suffix;
        $email = 'testEmail';

        $companyName = 'testCompanyName';
        $telephone = 'testTelephone';
        $city = 'testCity';
        $country = 'testCountry';
        $region = 'testRegion';
        $postCode = 'testPostCode';
        $quickbooksId = 'testQuickbooksId';

        $line1 = 'testLine1';
        $line2 = 'testLine2';
        $line3 = 'testLine3';
        $line4 = 'testLine4';
        $line5 = 'testLine5';
        $lines = [
            '0' => $line1,
            '1' => $line2,
            '2' => $line3,
            '3' => $line4,
            '4' => $line5,
        ];

        $billingShippingAddressId = 'billingShippingAddressId';

        $syncToken = 'testSyncToken';

        $responseBodyRead = [
            'Customer' => [
                'SyncToken' => $syncToken
            ]
        ];

        $addressData = [
            'quickbooks_id' => $quickbooksId
        ];

        $data = [
            'data' => [
                'Title' => $prefix,
                'GivenName' => $firstName,
                'MiddleName' => $middleName,
                'FamilyName' => $lastName,
                'FullyQualifiedName' => $name,
                'PrintOnCheckName' => $name,
                'PrimaryEmailAddr' => [
                    'Address' => $email
                ],
                'PrimaryPhone' => [
                    'FreeFormNumber' => $telephone,
                ],
                'BillAddr' => [
                    'Line1' => $line1,
                    'Line2' => $line2,
                    'Line3' => $line3,
                    'Line4' => $line4,
                    'Line5' => $line5,
                    'City' => $city,
                    'Country' => $country,
                    'CountrySubDivisionCode' => $region,
                    'PostalCode' => $postCode,
                    'Id' => $quickbooksId
                ],
                'ShipAddr' => [
                    'Line1' => $line1,
                    'Line2' => $line2,
                    'Line3' => $line3,
                    'Line4' => $line4,
                    'Line5' => $line5,
                    'City' => $city,
                    'Country' => $country,
                    'CountrySubDivisionCode' => $region,
                    'PostalCode' => $postCode,
                    'Id' => $quickbooksId
                ],
                'CompanyName' => $companyName,
                'Id' => $quickbooksId,
                'sparse' => true,
                'SyncToken' => $syncToken
            ],
            'uri' => 'company/:companyId/customer'
        ];

        #endregion initialData

        #region prepareAddresses
        $this->magentoCustomer->expects($this->once())
            ->method('getAddresses')
            ->willReturn([$this->addressInterface]);

        $this->magentoCustomer->expects($this->once())
            ->method('getDefaultBilling')
            ->willReturn($billingShippingAddressId);
        $this->magentoCustomer->expects($this->once())
            ->method('getDefaultShipping')
            ->willReturn($billingShippingAddressId);

        $billingShippingAddressData = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($billingShippingAddressData);
        $billingShippingAddressData->expects($this->exactly(2))
            ->method('load')
            ->with($this->identicalTo($billingShippingAddressId))
            ->willReturn(null);
        $billingShippingAddressData->expects($this->exactly(2))
            ->method('getDataModel')
            ->willReturn($this->addressInterface);

        #endregion prepareAddresses

        #region setAddressQuickbooksId

        $this->resourceConnection->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->connection->expects($this->exactly(3))
            ->method('select')
            ->willReturn($this->select);
        $this->select->expects($this->exactly(3))
            ->method('from')
            ->withConsecutive(
                [$this->identicalTo('quickbooks_billing_address')],
                [$this->identicalTo('quickbooks_billing_address')],
                [$this->identicalTo('quickbooks_shipping_address')]
            )
            ->willReturn($this->select);
        $this->addressInterface->expects($this->exactly(3))
            ->method('getId')
            ->willReturn($billingShippingAddressId);
        $this->select->expects($this->exactly(3))
            ->method('where')
            ->with($this->identicalTo(
                'address_id = ' . $billingShippingAddressId
            ))
            ->willReturn($this->select);
        $this->connection->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturn($addressData);
        $this->addressInterface->expects($this->exactly(6))
            ->method('getExtensionAttributes')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(null),
                $this->returnValue($this->addressExtension),
                $this->returnValue($this->addressExtension),
                $this->returnValue(null),
                $this->returnValue($this->addressExtension),
                $this->returnValue($this->addressExtension)
            ));
        $this->addressExtension->expects($this->exactly(2))
            ->method('setQuickbooksId')
            ->with($this->identicalTo($addressData['quickbooks_id']));
        $this->addressExtension->expects($this->exactly(2))
            ->method('getQuickbooksId')
            ->willReturn($addressData['quickbooks_id']);
        $this->addressInterface->expects($this->exactly(2))
            ->method('setExtensionAttributes')
            ->with($this->addressExtension)
            ->willReturn(null);
        $this->addressExtensionFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->addressExtension);

        #endregion setAddressQuickbooksId

        #region everythingElse
        $this->addressInterface->expects($this->once())
            ->method('getCompany')
            ->willReturn($companyName);
        $this->addressInterface->expects($this->once())
            ->method('getTelephone')
            ->willReturn($telephone);
        $this->addressInterface->expects($this->exactly(10))
            ->method('getStreet')
            ->willReturn($lines);
        $this->addressInterface->expects($this->exactly(2))
            ->method('getCity')
            ->willReturn($city);
        $this->addressInterface->expects(($this->exactly(2)))
            ->method('getCountryId')
            ->willReturn($country);
        $this->addressInterface->expects($this->exactly(2))
            ->method('getRegion')
            ->willReturn($this->region);
        $this->region->expects($this->exactly(2))
            ->method('getRegionCode')
            ->willReturn($region);
        $this->addressInterface->expects(($this->exactly(2)))
            ->method('getPostcode')
            ->willReturn($postCode);

        $quickbooksAttibute = $this->getMockBuilder(AttributeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->magentoCustomer->expects($this->exactly(2))
            ->method('getCustomAttribute')
            ->withConsecutive(
                [$this->identicalTo('quickbooks_id')],
                [$this->identicalTo('quickbooks_id')]
            )
            ->will($this->onConsecutiveCalls(
                $this->returnValue(true),
                $this->returnValue($quickbooksAttibute)
            ));

        $quickbooksAttibute->expects($this->once())
            ->method('getValue')
            ->willReturn($quickbooksId);

        $this->magentoCustomer->expects($this->any())
            ->method('getPrefix')
            ->willReturn($prefix);
        $this->magentoCustomer->expects($this->any())
            ->method('getFirstName')
            ->willReturn($firstName);
        $this->magentoCustomer->expects($this->any())
            ->method('getMiddleName')
            ->willReturn($middleName);
        $this->magentoCustomer->expects($this->any())
            ->method('getLastName')
            ->willReturn($lastName);
        $this->magentoCustomer->expects($this->any())
            ->method('getSuffix')
            ->willReturn($suffix);
        $this->magentoCustomer->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $this->magentoCustomer->expects($this->any())
            ->method('getEmail')
            ->willReturn($email);

        #endregion everythingElse

        #endregion PrepareData

        #region postData

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $encodedData = json_encode($data['data']);

        $successMessage = 'Customer was successfully synchronized';

        $this->jsonEncoder->expects($this->once())
            ->method('encode')
            ->with($this->identicalTo($data['data']))
            ->willReturn($encodedData);

        $this->quickbooksService->expects($this->once())
            ->method('post')
            ->with($encodedData, $data['uri'])
            ->willReturn($response);

        $this->quickbooksService->expects($this->once())
            ->method('read')
            ->with('company/:companyId/customer/:entityId', $quickbooksId)
            ->willReturn($response);

        $this->quickbooksService->expects($this->exactly(2))
            ->method('checkResponse')
            ->withConsecutive(
                [$this->identicalTo($response)],
                [$this->identicalTo($response)]
            )
            ->will($this->onConsecutiveCalls(
                $this->returnValue($responseBodyRead),
                $this->returnValue($data)
            ));

        $this->customerResource->expects($this->once())
            ->method('syncQuickbooksResponse')
            ->with(
                $this->identicalTo($data),
                $this->identicalTo($this->magentoCustomer)
            )
            ->willReturn(null);

        $this->messageManager->expects($this->never())
            ->method('addSuccessMessage')
            ->with($this->identicalTo($successMessage))
            ->willReturn(null);

        $result = $this->quickbooksCustomer->postCustomer(
            $this->magentoCustomer
        );

        $this->assertNotEmpty($result);

        #endregion postData
    }

    /**
     * @covers TNW\QuickbooksBasic\Model\Quickbooks\Customer::postCustomer
     */
    public function testPostCustomerWithNoQuickbooksIdAndNoBillingCompanyWithSuccessLookup()
    {
        #region PrepareData

        #region initialData
        $prefix = 'testPrefix';
        $firstName = 'testFirstName';
        $middleName = 'testMiddleName';
        $lastName = 'testLastName';
        $suffix = 'suffix';
        $name = $prefix . ' ' . $firstName . ' ' . $middleName . ' ' .
            $lastName . ' ' . $suffix;
        $email = 'testEmail';

        $companyName = 'testCompanyName';
        $telephone = 'testTelephone';
        $city = 'testCity';
        $country = 'testCountry';
        $region = 'testRegion';
        $postCode = 'testPostCode';
        $quickbooksId = 'testQuickbooksId';

        $line1 = 'testLine1';
        $line2 = 'testLine2';
        $line3 = 'testLine3';
        $line4 = 'testLine4';
        $line5 = 'testLine5';
        $lines = [
            '0' => $line1,
            '1' => $line2,
            '2' => $line3,
            '3' => $line4,
            '4' => $line5,
        ];

        $billingShippingAddressId = 'billingShippingAddressId';

        $queryString = /** @lang text */
            'select * from Customer' . " where PrimaryEmailAddr = '"
            . $email . "'";
        $responseBodyLookup = [
            'QueryResponse' => [
                'Customer' => [
                    'Id' => $quickbooksId
                ]
            ]
        ];

        $syncToken = 'testSyncToken';

        $responseBodyRead = [
            'Customer' => [
                'SyncToken' => $syncToken
            ]
        ];

        $addressData = [
            'quickbooks_id' => $quickbooksId
        ];

        $data = [
            'data' => [
                'Title' => $prefix,
                'GivenName' => $firstName,
                'MiddleName' => $middleName,
                'FamilyName' => $lastName,
                'FullyQualifiedName' => $name,
                'PrintOnCheckName' => $name,
                'PrimaryEmailAddr' => [
                    'Address' => $email
                ],
                'PrimaryPhone' => [
                    'FreeFormNumber' => $telephone,
                ],
                'BillAddr' => [
                    'Line1' => $line1,
                    'Line2' => $line2,
                    'Line3' => $line3,
                    'Line4' => $line4,
                    'Line5' => $line5,
                    'City' => $city,
                    'Country' => $country,
                    'CountrySubDivisionCode' => $region,
                    'PostalCode' => $postCode,
                    'Id' => $quickbooksId
                ],
                'ShipAddr' => [
                    'Line1' => $line1,
                    'Line2' => $line2,
                    'Line3' => $line3,
                    'Line4' => $line4,
                    'Line5' => $line5,
                    'City' => $city,
                    'Country' => $country,
                    'CountrySubDivisionCode' => $region,
                    'PostalCode' => $postCode,
                    'Id' => $quickbooksId
                ],
                'CompanyName' => $companyName,
                'Id' => $quickbooksId,
                'sparse' => true,
                'SyncToken' => $syncToken
            ],
            'uri' => 'company/:companyId/customer'
        ];

        #endregion initialData

        #region prepareAddresses
        $this->magentoCustomer->expects($this->once())
            ->method('getAddresses')
            ->willReturn([$this->addressInterface]);

        $this->magentoCustomer->expects($this->once())
            ->method('getDefaultBilling')
            ->willReturn($billingShippingAddressId);
        $this->magentoCustomer->expects($this->once())
            ->method('getDefaultShipping')
            ->willReturn($billingShippingAddressId);

        $billingShippingAddressData = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($billingShippingAddressData);
        $billingShippingAddressData->expects($this->exactly(2))
            ->method('load')
            ->with($this->identicalTo($billingShippingAddressId))
            ->willReturn(null);
        $billingShippingAddressData->expects($this->exactly(2))
            ->method('getDataModel')
            ->willReturn($this->addressInterface);

        #endregion prepareAddresses

        #region setAddressQuickbooksId

        $this->resourceConnection->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->connection->expects($this->exactly(3))
            ->method('select')
            ->willReturn($this->select);
        $this->select->expects($this->exactly(3))
            ->method('from')
            ->withConsecutive(
                [$this->identicalTo('quickbooks_billing_address')],
                [$this->identicalTo('quickbooks_billing_address')],
                [$this->identicalTo('quickbooks_shipping_address')]
            )
            ->willReturn($this->select);
        $this->addressInterface->expects($this->exactly(3))
            ->method('getId')
            ->willReturn($billingShippingAddressId);
        $this->select->expects($this->exactly(3))
            ->method('where')
            ->with($this->identicalTo('address_id = ' . $billingShippingAddressId))
            ->willReturn($this->select);
        $this->connection->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturn($addressData);
        $this->addressInterface->expects($this->exactly(6))
            ->method('getExtensionAttributes')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(null),
                $this->returnValue($this->addressExtension),
                $this->returnValue($this->addressExtension),
                $this->returnValue(null),
                $this->returnValue($this->addressExtension),
                $this->returnValue($this->addressExtension)
            ));
        $this->addressExtension->expects($this->exactly(2))
            ->method('setQuickbooksId')
            ->with($this->identicalTo($addressData['quickbooks_id']));
        $this->addressExtension->expects($this->exactly(2))
            ->method('getQuickbooksId')
            ->willReturn($addressData['quickbooks_id']);
        $this->addressInterface->expects($this->exactly(2))
            ->method('setExtensionAttributes')
            ->with($this->addressExtension)
            ->willReturn(null);
        $this->addressExtensionFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->addressExtension);

        #endregion setAddressQuickbooksId

        #region everythingElse
        $this->addressInterface->expects($this->exactly(2))
            ->method('getCompany')
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue(null),
                    $this->returnValue($companyName)
                )
            );
        $this->addressInterface->expects($this->once())
            ->method('getTelephone')
            ->willReturn($telephone);
        $this->addressInterface->expects($this->exactly(10))
            ->method('getStreet')
            ->willReturn($lines);
        $this->addressInterface->expects($this->exactly(2))
            ->method('getCity')
            ->willReturn($city);
        $this->addressInterface->expects(($this->exactly(2)))
            ->method('getCountryId')
            ->willReturn($country);
        $this->addressInterface->expects($this->exactly(2))
            ->method('getRegion')
            ->willReturn($this->region);
        $this->region->expects($this->exactly(2))
            ->method('getRegionCode')
            ->willReturn($region);
        $this->addressInterface->expects(($this->exactly(2)))
            ->method('getPostcode')
            ->willReturn($postCode);
        $this->magentoCustomer->expects($this->exactly(1))
            ->method('getCustomAttribute')
            ->withConsecutive(
                [$this->identicalTo('quickbooks_id')]
            )
            ->will($this->onConsecutiveCalls(
                $this->returnValue(false)
            ));

        $this->magentoCustomer->expects($this->any())
            ->method('getPrefix')
            ->willReturn($prefix);
        $this->magentoCustomer->expects($this->any())
            ->method('getFirstName')
            ->willReturn($firstName);
        $this->magentoCustomer->expects($this->any())
            ->method('getMiddleName')
            ->willReturn($middleName);
        $this->magentoCustomer->expects($this->any())
            ->method('getLastName')
            ->willReturn($lastName);
        $this->magentoCustomer->expects($this->any())
            ->method('getSuffix')
            ->willReturn($suffix);
        $this->magentoCustomer->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $this->magentoCustomer->expects($this->any())
            ->method('getEmail')
            ->willReturn($email);

        #endregion everythingElse

        #endregion PrepareData

        #region postData

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $encodedData = json_encode($data['data']);

        $successMessage = 'Customer was successfully synchronized';

        $this->jsonEncoder->expects($this->once())
            ->method('encode')
            ->with($this->identicalTo($data['data']))
            ->willReturn($encodedData);

        $this->quickbooksService->expects($this->once())
            ->method('post')
            ->with($encodedData, $data['uri'])
            ->willReturn($response);

        $responseLookup = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooksService->expects($this->once())
            ->method('query')
            ->with($this->identicalTo($queryString))
            ->willReturn($responseLookup);

        $this->quickbooksService->expects($this->once())
            ->method('read')
            ->with('company/:companyId/customer/:entityId', $quickbooksId)
            ->willReturn($response);

        $this->quickbooksService->expects($this->exactly(3))
            ->method('checkResponse')
            ->withConsecutive(
                [$this->identicalTo($responseLookup)],
                [$this->identicalTo($response)],
                [$this->identicalTo($response)]
            )
            ->will($this->onConsecutiveCalls(
                $this->returnValue($responseBodyLookup),
                $this->returnValue($responseBodyRead),
                $this->returnValue($data)
            ));

        $this->customerResource->expects($this->once())
            ->method('syncQuickbooksResponse')
            ->with(
                $this->identicalTo($data),
                $this->identicalTo($this->magentoCustomer)
            )
            ->willReturn(null);

        $this->messageManager->expects($this->never())
            ->method('addSuccessMessage')
            ->with($this->identicalTo($successMessage))
            ->willReturn(null);

        $result = $this->quickbooksCustomer->postCustomer($this->magentoCustomer);

        $this->assertNotEmpty($result);

        #endregion postData
    }

    /**
     * @covers TNW\QuickbooksBasic\Model\Quickbooks\Customer::postCustomer
     */
    public function testPostCustomerWithNoQuickbooksIdAndNoBillingCompanyWithFailLookup()
    {
        #region PrepareData

        #region initialData
        $prefix = 'testPrefix';
        $firstName = 'testFirstName';
        $middleName = 'testMiddleName';
        $lastName = 'testLastName';
        $suffix = 'suffix';
        $name = $prefix . ' ' . $firstName . ' ' .
            $middleName . ' ' . $lastName . ' ' . $suffix;
        $email = 'testEmail';


        $companyName = 'testCompanyName';
        $telephone = 'testTelephone';
        $city = 'testCity';
        $country = 'testCountry';
        $region = 'testRegion';
        $postCode = 'testPostCode';
        $quickbooksId = 'testQuickbooksId';
        $customerId = 'testCustomerId';

        $displayName = $name . ' @ ' . $companyName . '(' . $customerId . ')';

        $line1 = 'testLine1';
        $line2 = 'testLine2';
        $line3 = 'testLine3';
        $line4 = 'testLine4';
        $line5 = 'testLine5';
        $lines = [
            '0' => $line1,
            '1' => $line2,
            '2' => $line3,
            '3' => $line4,
            '4' => $line5,
        ];

        $billingShippingAddressId = 'billingShippingAddressId';

        $queryString = /** @lang text */
            'select * from Customer' . " where PrimaryEmailAddr = '"
            . $email . "'";
        $responseBodyLookup = [];

        $addressData = [
            'quickbooks_id' => $quickbooksId
        ];

        $data = [
            'data' => [
                'Title' => $prefix,
                'GivenName' => $firstName,
                'MiddleName' => $middleName,
                'FamilyName' => $lastName,
                'FullyQualifiedName' => $name,
                'PrintOnCheckName' => $name,
                'PrimaryEmailAddr' => [
                    'Address' => $email
                ],
                'PrimaryPhone' => [
                    'FreeFormNumber' => $telephone,
                ],
                'BillAddr' => [
                    'Line1' => $line1,
                    'Line2' => $line2,
                    'Line3' => $line3,
                    'Line4' => $line4,
                    'Line5' => $line5,
                    'City' => $city,
                    'Country' => $country,
                    'CountrySubDivisionCode' => $region,
                    'PostalCode' => $postCode,
                    'Id' => $quickbooksId
                ],
                'ShipAddr' => [
                    'Line1' => $line1,
                    'Line2' => $line2,
                    'Line3' => $line3,
                    'Line4' => $line4,
                    'Line5' => $line5,
                    'City' => $city,
                    'Country' => $country,
                    'CountrySubDivisionCode' => $region,
                    'PostalCode' => $postCode,
                    'Id' => $quickbooksId
                ],
                'CompanyName' => $companyName,
                'DisplayName' => $displayName
            ],
            'uri' => 'company/:companyId/customer'
        ];

        #endregion initialData

        #region prepareAddresses
        $this->magentoCustomer->expects($this->once())
            ->method('getAddresses')
            ->willReturn([$this->addressInterface]);

        $this->magentoCustomer->expects($this->once())
            ->method('getDefaultBilling')
            ->willReturn($billingShippingAddressId);
        $this->magentoCustomer->expects($this->once())
            ->method('getDefaultShipping')
            ->willReturn($billingShippingAddressId);

        $billingShippingAddressData = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($billingShippingAddressData);
        $billingShippingAddressData->expects($this->exactly(2))
            ->method('load')
            ->with($this->identicalTo($billingShippingAddressId))
            ->willReturn(null);
        $billingShippingAddressData->expects($this->exactly(2))
            ->method('getDataModel')
            ->willReturn($this->addressInterface);

        #endregion prepareAddresses

        #region setAddressQuickbooksId

        $this->resourceConnection->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->connection->expects($this->exactly(3))
            ->method('select')
            ->willReturn($this->select);
        $this->select->expects($this->exactly(3))
            ->method('from')
            ->withConsecutive(
                [$this->identicalTo('quickbooks_billing_address')],
                [$this->identicalTo('quickbooks_billing_address')],
                [$this->identicalTo('quickbooks_shipping_address')]
            )
            ->willReturn($this->select);
        $this->addressInterface->expects($this->exactly(3))
            ->method('getId')
            ->willReturn($billingShippingAddressId);
        $this->select->expects($this->exactly(3))
            ->method('where')
            ->with('address_id = ' . $billingShippingAddressId)
            ->willReturn($this->select);
        $this->connection->expects($this->exactly(2))
            ->method('fetchRow')
            ->willReturn($addressData);
        $this->addressInterface->expects($this->exactly(6))
            ->method('getExtensionAttributes')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(null),
                $this->returnValue($this->addressExtension),
                $this->returnValue($this->addressExtension),
                $this->returnValue(null),
                $this->returnValue($this->addressExtension),
                $this->returnValue($this->addressExtension)
            ));
        $this->addressExtension->expects($this->exactly(2))
            ->method('setQuickbooksId')
            ->with($this->identicalTo($addressData['quickbooks_id']));
        $this->addressExtension->expects($this->exactly(2))
            ->method('getQuickbooksId')
            ->willReturn($addressData['quickbooks_id']);
        $this->addressInterface->expects($this->exactly(2))
            ->method('setExtensionAttributes')
            ->with($this->addressExtension)
            ->willReturn(null);
        $this->addressExtensionFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->addressExtension);

        #endregion setAddressQuickbooksId

        #region everythingElse
        $this->addressInterface->expects($this->exactly(2))
            ->method('getCompany')
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue(null),
                    $this->returnValue($companyName)
                )
            );
        $this->addressInterface->expects($this->once())
            ->method('getTelephone')
            ->willReturn($telephone);
        $this->addressInterface->expects($this->exactly(10))
            ->method('getStreet')
            ->willReturn($lines);
        $this->addressInterface->expects($this->exactly(2))
            ->method('getCity')
            ->willReturn($city);
        $this->addressInterface->expects(($this->exactly(2)))
            ->method('getCountryId')
            ->willReturn($country);
        $this->addressInterface->expects($this->exactly(2))
            ->method('getRegion')
            ->willReturn($this->region);
        $this->region->expects($this->exactly(2))
            ->method('getRegionCode')
            ->willReturn($region);
        $this->addressInterface->expects(($this->exactly(2)))
            ->method('getPostcode')
            ->willReturn($postCode);

        $this->magentoCustomer->expects($this->exactly(1))
            ->method('getCustomAttribute')
            ->withConsecutive(
                [$this->identicalTo('quickbooks_id')]
            )
            ->will($this->onConsecutiveCalls(
                $this->returnValue(false)
            ));

        $this->magentoCustomer->expects($this->any())
            ->method('getPrefix')
            ->willReturn($prefix);
        $this->magentoCustomer->expects($this->any())
            ->method('getFirstName')
            ->willReturn($firstName);
        $this->magentoCustomer->expects($this->any())
            ->method('getMiddleName')
            ->willReturn($middleName);
        $this->magentoCustomer->expects($this->any())
            ->method('getLastName')
            ->willReturn($lastName);
        $this->magentoCustomer->expects($this->any())
            ->method('getSuffix')
            ->willReturn($suffix);
        $this->magentoCustomer->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $this->magentoCustomer->expects($this->any())
            ->method('getEmail')
            ->willReturn($email);

        #endregion everythingElse

        #endregion PrepareData

        #region postData

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $encodedData = json_encode($data['data']);

        $successMessage = 'Customer was successfully synchronized';

        $this->jsonEncoder->expects($this->once())
            ->method('encode')
            ->with($this->identicalTo($data['data']))
            ->willReturn($encodedData);

        $this->quickbooksService->expects($this->once())
            ->method('post')
            ->with($encodedData, $data['uri'])
            ->willReturn($response);

        $responseLookup = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooksService->expects($this->once())
            ->method('query')
            ->with($this->identicalTo($queryString))
            ->willReturn($responseLookup);

        $this->quickbooksService->expects($this->exactly(2))
            ->method('checkResponse')
            ->withConsecutive(
                [$this->identicalTo($responseLookup)],
                [$this->identicalTo($response)]
            )
            ->will($this->onConsecutiveCalls(
                $this->returnValue($responseBodyLookup),
                $this->returnValue($data)
            ));

        $this->customerResource->expects($this->once())
            ->method('syncQuickbooksResponse')
            ->with(
                $this->identicalTo($data),
                $this->identicalTo($this->magentoCustomer)
            )
            ->willReturn(null);

        $this->messageManager->expects($this->never())
            ->method('addSuccessMessage')
            ->with($this->identicalTo($successMessage))
            ->willReturn(null);

        $this->magentoCustomer->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $result =
            $this->quickbooksCustomer->postCustomer($this->magentoCustomer);

        $this->assertNotEmpty($result);

        #endregion postData
    }

    /**
     * @covers TNW\QuickbooksBasic\Model\Quickbooks\Customer::read
     */
    public function testRead()
    {
        $quickbooksId = 'testQuickbooksId';

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooksService->expects($this->once())
            ->method('read')
            ->with('company/:companyId/customer/:entityId', $quickbooksId)
            ->willReturn($response);

        $result = $this->quickbooksCustomer->read($quickbooksId);

        $this->assertInstanceOf(\Zend_Http_Response::class, $result);
    }

    /**
     * @covers TNW\QuickbooksBasic\Model\Quickbooks\Customer::getDisplayName
     */
    public function testGetDisplayName()
    {
        $companyName = 'testCompanyName';
        $customerId = '123';
        $prefix = 'prefix';
        $firstName = 'firstNamefirstNamefirstNamefirstNamefirstNamefirstNamefirstName firstNamefirstNamefirstNamefirstNamefirstNamefirstNamefirstNamefirstNamefirstName firstNamefirstNamefirstNamefirstNamefirstNamefirstNamefirstNamefirstNamefirstName firstNamefirstNamefirstNamefirstNamefirstNamefirstNamefirstNamefirstNamefirstName';
        $middleName = 'middleName';
        $lastName = 'lastName';
        $suffix = 'suffix';

        $this->magentoCustomer->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $this->magentoCustomer->expects($this->once())
            ->method('getPrefix')
            ->willReturn($prefix);
        $this->magentoCustomer->expects($this->once())
            ->method('getFirstname')
            ->willReturn($firstName);
        $this->magentoCustomer->expects($this->once())
            ->method('getMiddlename')
            ->willReturn($middleName);
        $this->magentoCustomer->expects($this->once())
            ->method('getLastname')
            ->willReturn($lastName);
        $this->magentoCustomer->expects($this->once())
            ->method('getSuffix')
            ->willReturn($suffix);

        $result = $this->quickbooksCustomer->getDisplayName(
            $this->magentoCustomer,
            $companyName
        );

        $this->assertTrue(strlen($result) <= 100);
        $this->assertTrue(strpos($result, $customerId) !== false);
    }

    /**
     * @covers TNW\QuickbooksBasic\Model\Quickbooks\Customer::syncCustomerQuickbooksResponse
     */
    public function testSyncCustomerQuickbooksResponse()
    {
        $responseBody = [
            'body' => 'testResponseBody'
        ];

        $this->customerResource->expects($this->once())
            ->method('syncQuickbooksResponse')
            ->with(
                $this->identicalTo($responseBody),
                $this->identicalTo($this->magentoCustomer)
            )
            ->willReturn(null);

        $this->quickbooksCustomer->syncCustomerQuickbooksResponse(
            $responseBody,
            $this->magentoCustomer
        );
    }

    /**
     * @covers TNW\QuickbooksBasic\Model\Quickbooks\Customer::lookupQuickbooksCustomerIdByEmail
     */
    public function testLookupQuickbooksCustomerIdByEmail()
    {

        $email = 'testEmail';
        $quickbooksId = 'testQuickbooksId';
        $queryString = /** @lang text */
            'select * from Customer' . " where PrimaryEmailAddr = '"
            . $email . "'";
        $responseBody = [
            'QueryResponse' => [
                'Customer' => [
                    'Id' => $quickbooksId
                ]
            ]
        ];

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooksService->expects($this->once())
            ->method('query')
            ->with($this->identicalTo($queryString))
            ->willReturn($response);

        $this->quickbooksService->expects($this->exactly(1))
            ->method('checkResponse')
            ->with($this->identicalTo($response))
            ->willReturn($responseBody);

        $result = $this->quickbooksCustomer->lookupQuickbooksCustomerByEmail(
            $email
        );

        $this->assertEquals(
            $responseBody['QueryResponse']['Customer'],
            $result
        );
    }

    protected function setUp()
    {
        $this->magentoCustomer = $this->getMockBuilder(MagentoCustomer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configFactory = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooksService = $this->getMock(
            $originalClassName = QuickbooksService::class,
            $methods = [
                'query',
                'checkResponse',
                'read',
                'post'
            ],
            $arguments = ['config', $this->config],
            $mockClassName = '',
            $callOriginalConstructor = false
        );

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
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

        $this->customerResource = $this->getMockBuilder(QuickbooksCustomerResource::class)
            ->setMethods(['syncQuickbooksResponse'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressFactory = $this->getMockBuilder(AddressFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressExtensionFactory = $this->getMockBuilder(AddressExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressExtension = $this->getMockBuilder(AddressExtension::class)
            ->setMethods(['setQuickbooksId', 'getQuickbooksId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressInterface = $this->getMockBuilder(AddressInterface::class)
//            ->setMethods([
//                'getTelephone',
//                'getStreetLine',
//                'getCity',
//                'getCountry',
//                'getRegion',
//                'getPostCode',
//                'getCompany',
//                'getId',
//                'setExtensionAttributes',
//                'getExtensionAttributes',
//                'getCustomAttribute'
//            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->region = $this->getMockBuilder(RegionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quickbooksCustomer = new QuickbooksCustomer(
            $this->configFactory,
            $this->config,
            $this->urlBuilder,
            $this->jsonEncoder,
            $this->jsonDecoder,
            $this->logger,
            $this->quickbooksConfig,
            $this->quickbooksService,
            $this->customerResource,
            $this->addressFactory,
            $this->resourceConnection,
            $this->addressExtensionFactory,
            $this->messageManager
        );
    }

    protected function tearDown()
    {
        $this->magentoCustomer = null;
        $this->configFactory = null;
        $this->config = null;
        $this->quickbooksService = null;
        $this->urlBuilder = null;
        $this->jsonEncoder = null;
        $this->jsonDecoder = null;
        $this->logger = null;
        $this->quickbooksConfig = null;
        $this->customerResource = null;
        $this->messageManager = null;
        $this->addressFactory = null;
        $this->resourceConnection = null;
        $this->addressExtensionFactory = null;
        $this->addressExtension = null;
        $this->addressInterface = null;
        $this->connection = null;
        $this->select = null;
        $this->region = null;
        $this->quickbooksCustomer = null;
    }
}
