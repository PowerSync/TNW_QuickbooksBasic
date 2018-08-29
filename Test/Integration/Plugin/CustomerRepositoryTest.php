<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Integration\Plugin;

use TNW\QuickbooksBasic\TokenData;
use Magento\Framework\App\Area;
use Magento\Framework\Json\Encoder;
use Magento\Customer\Model\Customer;
use Magento\TestFramework\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\App\State as AppState;
use Magento\TestFramework\Interception\PluginList;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer as QuickbooksCustomer;
use TNW\QuickbooksBasic\Plugin\AddressRepository as AddressRepositoryPlugin;
use TNW\QuickbooksBasic\Plugin\CustomerRepository as CustomerRepositoryPlugin;

/**
 * Class CustomerRepositoryTest
 * @package TNW\QuickbooksBasic\Test\Integration\Plugin
 */
class CustomerRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var  CustomerRepositoryPlugin */
    protected $customerRepository;

    /** @var  Encoder */
    protected $encoder;

    /** @var  \Zend_Oauth_Token_Access mock */
    protected $accessToken;

    /** @var  QuickbooksCustomer */
    protected $customer;

    /** @var ObjectManager */
    protected $objectManager;

    protected function setUp()
    {
        $this->accessToken = $this->getMockBuilder(
            \Zend_Oauth_Token_Access::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        /** @var TokenData mock $tokenData */
        $tokenData = $this->getMockBuilder(TokenData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAccessToken'])
            ->getMock();
        $tokenData->method('getAccessToken')->willReturn($this->accessToken);

        $this->objectManager = ObjectManager::getInstance();
        $this->objectManager->configure(
            [TokenData::class => ['shared' => true]]
        );
        $this->objectManager->addSharedInstance($tokenData, TokenData::class);

        $this->encoder = $this->objectManager->create(Encoder::class);

        parent::setUp();
    }

    public function testTheCustomerRepositoryPluginRegistered()
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->create(PluginList::class);

        /** @var array $pluginInfo */
        $pluginInfo = $pluginList->get(CustomerRepositoryInterface::class, []);

        $this->assertSame(
            CustomerRepositoryPlugin::class,
            $pluginInfo['QuickbooksCustomerRepository']['instance']
        );
    }

    /**
     * @magentoDataFixture loadAfterSave
     * @dataProvider       executeDataProvider
     *
     * @param array $requsetBody
     * @param array $requestBodyAfterSyncAddress
     * @param array $responseBodyAfterRead
     * @param array $responseBodyAfterPost
     * @param array $status
     * @param array $apiUri
     * @param array $requestUri
     */
    public function testAfterSave(
        array $requsetBody,
        array $requestBodyAfterSyncAddress,
        array $responseBodyAfterRead,
        array $responseBodyAfterPost,
        array $status,
        array $apiUri,
        array $requestUri
    ) {
        $applicationState = $this->objectManager->get(AppState::class);
        $applicationState->setAreaCode(Area::AREA_ADMINHTML);

        /** @var string $encodedRequestBody */
        $encodedRequestBody = $this->encoder->encode($requsetBody);

        /** @var string $encodedRequestBodyAfterAddrSync */
        $encodedRequestBodyAfterAddrSync = $this->encoder->encode(
            $requestBodyAfterSyncAddress
        );

        /** @var string $encodedResponseBodyAfterRead */
        $encodedResponseBodyAfterRead =
            $this->encoder->encode($responseBodyAfterRead);

        /** @var string $encodedResponseBodyAfterPost */
        $encodedResponseBodyAfterPost =
            $this->encoder->encode($responseBodyAfterPost);

        /** @var \Zend_Http_Response mock $response */
        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->exactly(8))
            ->method('getBody')
            ->will($this->onConsecutiveCalls(
                $this->returnValue($encodedResponseBodyAfterRead),
                $this->returnValue($encodedResponseBodyAfterRead),
                $this->returnValue($encodedResponseBodyAfterPost),
                $this->returnValue($encodedResponseBodyAfterPost),
                $this->returnValue($encodedResponseBodyAfterRead),
                $this->returnValue($encodedResponseBodyAfterRead),
                $this->returnValue($encodedResponseBodyAfterPost),
                $this->returnValue($encodedResponseBodyAfterPost)
            ));
        $response->expects($this->exactly(8))
            ->method('getStatus')
            ->willReturn($status['code']);

        $client = $this->getMockBuilder(\Zend_Oauth_Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->exactly(4))
            ->method('setUri')
            ->withConsecutive(
                [$this->identicalTo($apiUri['uri'] . $requestUri['read'])],
                [$this->identicalTo($apiUri['uri'] . $requestUri['post'])]
            )
            ->willReturn(null);
        $client->expects($this->exactly(4))
            ->method('setMethod')
            ->withConsecutive(
                [$this->identicalTo("GET")],
                [$this->identicalTo("POST")]
            )
            ->willReturn(null);
        $client->expects($this->exactly(4))
            ->method('setHeaders')
            ->with('Accept', 'application/json')
            ->willReturn(null);
        $client->expects($this->exactly(2))
            ->method('setRawData')
            ->withConsecutive(
                [$encodedRequestBody, 'application/json'],
                [$encodedRequestBodyAfterAddrSync, 'application/json']
            )
            ->willReturn(null);
        $client->expects($this->exactly(4))
            ->method('request')
            ->willReturn($response);

        $this->accessToken->expects($this->exactly(4))
            ->method('getHttpClient')
            ->willReturn($client);

        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->create(
            CustomerRepositoryInterface::class
        );

        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $customerRepository->get('customer@example.com');

        $customerRepository->save($customer);

        /**
         * reload customer after sync to check if quickbooks id,
         * sync token and sync status was saved
         */

        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $customerRepository->get('customer@example.com');

        /** @var MessageManager $messenger */
        $messenger = $this->objectManager->get(MessageManager::class);

        $this->assertEquals(
            2,
            $customer->getCustomAttribute('quickbooks_id')->getValue()
        );
        $this->assertEquals(
            3,
            $customer->getCustomAttribute('quickbooks_sync_token')->getValue()
        );
        $this->assertEquals(
            1,
            $customer->getCustomAttribute('quickbooks_sync_status')->getValue()
        );
        $this->assertEquals(
            "Magento customer 'John Smith' was successfully synchronized",
            $messenger->getMessages()->getLastAddedMessage()->getText()
        );
    }

    /**
     * @magentoDataFixture loadAfterSaveThrowException
     */
    public function testAfterSaveException()
    {
        /** @var string $exception */
        $exception = 'Synchronization of customer failed. Please try later.';

        /** @var QuickbooksCustomer mock $quickbooksCustomer */
        $quickbooksCustomer = $this->getMockBuilder(QuickbooksCustomer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quickbooksCustomer->method('postCustomer')
            ->will($this->throwException(new \Exception($exception)));

        $this->objectManager->configure(
            [QuickbooksCustomer::class => ['shared' => true]]
        );
        $this->objectManager->addSharedInstance(
            $quickbooksCustomer,
            QuickbooksCustomer::class
        );

        $state = $this->objectManager->get(AppState::class);
        $state->setAreaCode(Area::AREA_ADMINHTML);

        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->create(
            CustomerRepositoryInterface::class
        );

        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $customerRepository->get('customer@example.com');

        $customerRepository->save($customer);

        /** @var MessageManager $messanger */
        $messenger = $this->objectManager->get(MessageManager::class);

        $this->assertSame(
            $exception,
            $messenger->getMessages()->getLastAddedMessage()->getText()
        );
    }

    /**
     * @magentoDataFixture loadAfterSaveCustomerNotActive
     */
    public function testAfterSaveAutosyncDisabled()
    {
        /** @var AppState $state */
        $state = $this->objectManager->get(AppState::class);
        $state->setAreaCode(Area::AREA_FRONTEND);

        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->create(
            CustomerRepositoryInterface::class
        );

        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $customerRepository->get('customer@example.com');

        $customer->setCustomAttribute('quickbooks_sync_status', 1);
        $customerRepository->save($customer);

        /** reload customer to get new value for quickbooks_sync_status */

        /** @var Customer $reloadedCustomer */
        $reloadedCustomer = $this->objectManager->create(Customer::class);
        $reloadedCustomer->load(1);

        $this->assertEquals(
            0,
            $reloadedCustomer->getDataModel()
                ->getCustomAttribute('quickbooks_sync_status')->getValue()
        );
    }

    protected function tearDown()
    {
        $this->objectManager->removeSharedInstance(
            CustomerRepositoryPlugin::class
        );
        $this->objectManager->removeSharedInstance(
            AddressRepositoryPlugin::class
        );

        $this->cleanUpAddressQuickbooksIds();
        $this->cleanUpCustomer();

        parent::tearDown();
    }

    private function cleanUpAddressQuickbooksIds()
    {
        /** @var ResourceConnection $connection */
        $connection = $this->objectManager->get(ResourceConnection::class);
        $connection->getConnection()->truncateTable(
            QuickbooksCustomer::BILLING_ADDRESS_QUICKBOOKS_TABLE
        );
        $connection->getConnection()->truncateTable(
            QuickbooksCustomer::SHIPPINGG_ADDRESS_QUICKBOOKS_TABLE
        );
    }

    private function cleanUpCustomer()
    {
        /** @codingStandardsIgnoreStart */
        include __DIR__ . '/../_files/customer_cleanup.php';
        /** @codingStandardsIgnoreEnd */
    }

    /** @codingStandardsIgnoreStart */
    public static function loadAfterSave()
    {
        include __DIR__ . '/../_files/customer_with_quickbooks_attrs.php';
        include __DIR__ . "/../_files/customer_address.php";
        include __DIR__ . "/../_files/company.php";
        include __DIR__ . "/../_files/general_active.php";
        include __DIR__ . "/../_files/autosync_enabled.php";
    }

    public static function loadAfterSaveThrowException()
    {
        include __DIR__ . '/../_files/customer.php';
        include __DIR__ . "/../_files/general_active.php";
        include __DIR__ . "/../_files/autosync_enabled.php";
    }

    public static function loadAfterSaveCustomerNotActive()
    {
        include __DIR__ . "/../_files/customer.php";
        include __DIR__ . "/../_files/general_active.php";
        include __DIR__ . "/../_files/autosync_disabled.php";

    }

    /** @codingStandardsIgnoreEnd */

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'RequestBody' => [
                    'Title' => 'Mr.',
                    'GivenName' => 'John',
                    'MiddleName' => 'A',
                    'FamilyName' => 'Smith',
                    'FullyQualifiedName' => 'Mr. John A Smith Esq.',
                    'PrintOnCheckName' => 'Mr. John A Smith Esq.',
                    'PrimaryEmailAddr' =>
                        [
                            'Address' => 'customer@example.com',
                        ],
                    'PrimaryPhone' =>
                        [
                            'FreeFormNumber' => '3468676',
                        ],
                    'BillAddr' =>
                        [
                            'Line1' => 'Green str, 67',
                            'Line2' => '',
                            'Line3' => '',
                            'Line4' => '',
                            'Line5' => '',
                            'City' => 'TestCity',
                            'Country' => 'US',
                            'CountrySubDivisionCode' => 'AL',
                            'PostalCode' => '75477',
                        ],
                    'ShipAddr' =>
                        [
                            'Line1' => 'Green str, 67',
                            'Line2' => '',
                            'Line3' => '',
                            'Line4' => '',
                            'Line5' => '',
                            'City' => 'TestCity',
                            'Country' => 'US',
                            'CountrySubDivisionCode' => 'AL',
                            'PostalCode' => '75477',
                        ],
                    'CompanyName' => 'TestCompanyName',
                    'Id' => "2",
                    'sparse' => true,
                    'SyncToken' => 2,
                ],
                '$requestBodyAfterSyncAddress' => [
                    'Title' => 'Mr.',
                    'GivenName' => 'John',
                    'MiddleName' => 'A',
                    'FamilyName' => 'Smith',
                    'FullyQualifiedName' => 'Mr. John A Smith Esq.',
                    'PrintOnCheckName' => 'Mr. John A Smith Esq.',
                    'PrimaryEmailAddr' =>
                        [
                            'Address' => 'customer@example.com',
                        ],
                    'PrimaryPhone' =>
                        [
                            'FreeFormNumber' => '3468676',
                        ],
                    'BillAddr' =>
                        [
                            'Line1' => 'Green str, 67',
                            'Line2' => '',
                            'Line3' => '',
                            'Line4' => '',
                            'Line5' => '',
                            'City' => 'TestCity',
                            'Country' => 'US',
                            'CountrySubDivisionCode' => 'AL',
                            'PostalCode' => '75477',
                            'Id' => '1'
                        ],
                    'ShipAddr' =>
                        [
                            'Line1' => 'Green str, 67',
                            'Line2' => '',
                            'Line3' => '',
                            'Line4' => '',
                            'Line5' => '',
                            'City' => 'TestCity',
                            'Country' => 'US',
                            'CountrySubDivisionCode' => 'AL',
                            'PostalCode' => '75477',
                            'Id' => '1'
                        ],
                    'CompanyName' => 'TestCompanyName',
                    'Id' => "2",
                    'sparse' => true,
                    'SyncToken' => 2,
                ],
                'responseBodyAfterRead' => [
                    'Customer' => [
                        'Id' => 2,
                        'SyncToken' => 2,
                        'Title' => 'Mr.',
                        'GivenName' => 'John',
                        'MiddleName' => 'A',
                        'FamilyName' => 'Smith',
                        'FullyQualifiedName' => 'Mr. John A Smith Esq.',
                        'PrintOnCheckName' => 'Mr. John A Smith Esq.',
                        'PrimaryEmailAddr' =>
                            [
                                'Address' => 'customer@example.com',
                            ],
                        'PrimaryPhone' =>
                            [
                                'FreeFormNumber' => '3468676',
                            ],
                        'BillAddr' =>
                            [
                                'Line1' => 'Green str, 67',
                                'Line2' => '',
                                'Line3' => '',
                                'Line4' => '',
                                'Line5' => '',
                                'City' => 'TestCity',
                                'Country' => 'US',
                                'CountrySubDivisionCode' => 'AL',
                                'PostalCode' => '75477',
                            ],
                        'ShipAddr' =>
                            [
                                'Line1' => 'Green str, 67',
                                'Line2' => '',
                                'Line3' => '',
                                'Line4' => '',
                                'Line5' => '',
                                'City' => 'TestCity',
                                'Country' => 'US',
                                'CountrySubDivisionCode' => 'AL',
                                'PostalCode' => '75477',
                            ],
                        'CompanyName' => 'TestCompanyName',
                        'DisplayName' =>
                            'Mr. John A Smith Esq. @ TestCompanyName(1)',
                    ]
                ],
                'responseBodyAfterPost' => [
                    'Customer' => [
                        'Title' => 'Mr.',
                        'GivenName' => 'John',
                        'MiddleName' => 'A',
                        'FamilyName' => 'Smith',
                        'FullyQualifiedName' => 'Mr. John A Smith Esq.',
                        'PrintOnCheckName' => 'Mr. John A Smith Esq.',
                        'PrimaryEmailAddr' =>
                            [
                                'Address' => 'customer@example.com',
                            ],
                        'PrimaryPhone' =>
                            [
                                'FreeFormNumber' => '3468676',
                            ],
                        'BillAddr' =>
                            [
                                'Id' => 1,
                                'SyncToken' => 2,
                                'Line1' => 'Green str, 67',
                                'Line2' => '',
                                'Line3' => '',
                                'Line4' => '',
                                'Line5' => '',
                                'City' => 'TestCity',
                                'Country' => 'US',
                                'CountrySubDivisionCode' => 'AL',
                                'PostalCode' => '75477',
                            ],
                        'ShipAddr' =>
                            [
                                'Id' => 1,
                                'SyncToken' => 2,
                                'Line1' => 'Green str, 67',
                                'Line2' => '',
                                'Line3' => '',
                                'Line4' => '',
                                'Line5' => '',
                                'City' => 'TestCity',
                                'Country' => 'US',
                                'CountrySubDivisionCode' => 'AL',
                                'PostalCode' => '75477',
                            ],
                        'CompanyName' => 'TestCompanyName',
                        'Id' => 2,
                        'SyncToken' => 3,
                    ]
                ],
                'status' => [
                    'code' => 200
                ],
                'apiUri' => [
                    'uri' => 'https://quickbooks.api.intuit.com/v3/'
                ],
                'requestUri' => [
                    'read' => 'company/testCompany/customer/2',
                    'post' => 'company/testCompany/customer'
                ]
            ]
        ];
    }
}
