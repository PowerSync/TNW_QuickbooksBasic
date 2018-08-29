<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Test\Integration\Plugin;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Area;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\TestFramework\App\State as AppState;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\ObjectManager;
use TNW\QuickbooksBasic\Model\Quickbooks\Customer as QuickbooksCustomer;
use TNW\QuickbooksBasic\Plugin\AddressRepository as AddressRepositoryPlugin;
use TNW\QuickbooksBasic\Service\Quickbooks as QuickbooksService;
use TNW\QuickbooksBasicBusiness\Service\Quickbooks as QuickbooksBusinessService;

/**
 * Class AddressRepositoryTest
 *
 * @package TNW\QuickbooksBasic\Test\Integration\Plugin
 */
class AddressRepositoryTest extends \PHPUnit_Framework_TestCase
{
    const TNW_QUICKBOOKS_BUSINESS = 'TNW_QuickbooksBusiness';

    /** @var  ObjectManager */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->cleanUpSharedInstances();

        parent::setUp();
    }

    public function testTheAddressRepositoryPluginRegistered()
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->create(PluginList::class);

        /** @var array $pluginInfo */
        $pluginInfo = $pluginList->get(AddressRepositoryInterface::class, []);

        $this->assertSame(
            AddressRepositoryPlugin::class,
            $pluginInfo['QuickbooksAddressRepository']['instance']
        );
    }

    /**
     * @magentoDataFixture loadAfterSave
     * @dataProvider       afterSaveDataProvider
     *
     * @param array $responseBodyAfterQuery
     * @param array $responseBodyAfterPost
     */
    public function testAfterSave(
        array $responseBodyAfterQuery,
        array $responseBodyAfterPost
    ) {
        $this->replaceQuickbooksService(
            $responseBodyAfterQuery,
            $responseBodyAfterPost
        );

        /** @var AddressRepositoryInterface $addressRepository */
        $addressRepository = $this->objectManager->create(
            AddressRepositoryInterface::class
        );

        /** @var AddressInterface $address */
        $address = $addressRepository->getById(1);

        $addressRepository->save($address);

        $magentoCustomer = $this->objectManager->create(MagentoCustomer::class);
        $magentoCustomer->load(1);

        $this->assertEquals(
            1,
            $magentoCustomer->getDataModel()
                ->getCustomAttribute('quickbooks_id')->getValue()
        );

        $this->assertEquals(
            2,
            $magentoCustomer->getDataModel()
                ->getCustomAttribute('quickbooks_sync_token')->getValue()
        );

        $this->assertEquals(
            1,
            $magentoCustomer->getDataModel()
                ->getCustomAttribute('quickbooks_sync_status')->getValue()
        );
    }

    /**
     * @magentoDataFixture loadAfterSave
     */
    public function testAfterSaveException()
    {
        /** @var QuickbooksCustomer mock $quickbooksCustomer */
        $quickbooksCustomer = $this->getMockBuilder(QuickbooksCustomer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quickbooksCustomer->method('postCustomer')
            ->will($this->throwException(new \Exception('test exception')));

        $this->objectManager->configure(
            [QuickbooksCustomer::class => ['shared' => true]]
        );

        $this->objectManager->addSharedInstance(
            $quickbooksCustomer,
            QuickbooksCustomer::class
        );

        $state = $this->objectManager->get(AppState::class);
        $state->setAreaCode(Area::AREA_ADMINHTML);

        /** @var AddressRepositoryInterface $addressRepository */
        $addressRepository = $this->objectManager->create(
            AddressRepositoryInterface::class
        );

        /** @var AddressInterface $address */
        $address = $addressRepository->getById(1);

        $addressRepository->save($address);

        /** @var MessageManager $messanger */
        $messenger = $this->objectManager->get(MessageManager::class);

        $this->assertSame(
            'test exception',
            $messenger->getMessages()->getLastAddedMessage()->getText()
        );
    }

    /**
     * @magentoDataFixture loadAfterSaveAutosyncDisabled
     */
    public function testAfterSaveAutosyncDisabled()
    {
        /** @var AppState $state */
        $state = $this->objectManager->get(AppState::class);
        $state->setAreaCode(Area::AREA_FRONTEND);

        /** @var AddressRepositoryInterface $addressRepository */
        $addressRepository = $this->objectManager->create(
            AddressRepositoryInterface::class
        );

        /** @var AddressInterface $address */
        $address = $addressRepository->getById(1);

        $addressRepository->save($address);

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

    /**
     * @param array $responseBodyAfterQuery
     * @param array $responseBodyAfterPost
     */
    private function replaceQuickbooksService(
        array $responseBodyAfterQuery,
        array $responseBodyAfterPost
    ) {
        /** @var QuickbooksService mock $quickbooksServiceMock */
        $quickbooksServiceMock = $this->getMockBuilder(QuickbooksService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quickbooksServiceMock->expects($this->once())
            ->method('query')
            ->willReturn(null);
        $quickbooksServiceMock->expects($this->once())
            ->method('post')
            ->willReturn(null);
        $quickbooksServiceMock->expects($this->exactly(2))
            ->method('checkResponse')
            ->will($this->onConsecutiveCalls(
                $this->returnValue($responseBodyAfterQuery),
                $this->returnValue($responseBodyAfterPost)
            ));

        if ($this->checkBusinessExists()) {
            $this->objectManager->configure(
                [QuickbooksBusinessService::class => ['shared' => true]]
            );
            $this->objectManager->addSharedInstance(
                $quickbooksServiceMock,
                QuickbooksBusinessService::class
            );
        } else {
            $this->objectManager->configure(
                [QuickbooksService::class => ['shared' => true]]
            );
            $this->objectManager->addSharedInstance(
                $quickbooksServiceMock,
                QuickbooksService::class
            );
        }
    }

    /**
     * @return bool
     */
    private function checkBusinessExists()
    {
        $registrar = new ComponentRegistrar();
        $paths = $registrar->getPaths(ComponentRegistrar::MODULE);

        return array_key_exists(self::TNW_QUICKBOOKS_BUSINESS, $paths);
    }

    private function cleanUpSharedInstances()
    {
        $this->objectManager->removeSharedInstance(QuickbooksCustomer::class);
        $this->objectManager->removeSharedInstance(
            AddressRepositoryPlugin::class
        );
    }

    private function cleanUpCustomer()
    {
        /** @codingStandardsIgnoreStart */
        include __DIR__ . '/../_files/customer_cleanup.php';
        /** @codingStandardsIgnoreEnd */
    }

    protected function tearDown()
    {
        $this->cleanUpSharedInstances();

        $this->cleanUpCustomer();

        parent::tearDown();
    }

    /** @codingStandardsIgnoreStart */

    public static function loadAfterSave()
    {
        include __DIR__ . "/../_files/general_active.php";
        include __DIR__ . "/../_files/autosync_enabled.php";
        include __DIR__ . "/../_files/customer.php";
        include __DIR__ . "/../_files/customer_address.php";
    }

    public static function loadAfterSaveAutosyncDisabled()
    {
        include __DIR__ . "/../_files/general_active.php";
        include __DIR__ . "/../_files/autosync_disabled.php";
        include __DIR__ . "/../_files/customer.php";
        include __DIR__ . "/../_files/customer_address.php";
    }

    /**
     * @return array
     */
    public function afterSaveDataProvider()
    {
        return [
            [
                'responseBodyAfterQuery' => [
                    'QueryResponse' => [
                        'Customer' => [
                            'Id' => 1,
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
                        'Id' => 1,
                        'sparse' => true,
                        'SyncToken' => 2,
                        "DisplayName" =>
                            "Mr. John A Smith Esq. @ TestCompanyName(1)"
                    ]
                ],
            ]
        ];
    }

    /** @codingStandardsIgnoreEnd */
}
